<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\AttendanceMarkingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceMarkingService $attendanceService
    ) {}

    /**
     * Show QR scanner page
     */
    public function index()
    {
        $student = Auth::user();
        $summary = $this->attendanceService->getStudentSummary($student);

        return view('student.attendance.index', compact('summary'));
    }

    /**
     * Process scanned QR code
     */
    public function scan(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'qr_data' => ['required', 'string', 'max:65535'],
                'from_queue' => ['nullable', 'boolean'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'We could not read that scan. Please try scanning again.',
                ], 422);
            }

            $data = $validator->validated();

            $student = Auth::user();
            $result = $this->attendanceService->markAttendance(
                $data['qr_data'],
                $student,
                $request->ip(),
                $request->userAgent() ?? ''
            );

            if ($result['success']) {
                $session = $result['session'];
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'course' => $session?->schedule?->course?->name ?? '—',
                    'section' => $session?->schedule?->section?->name ?? '—',
                    'marked_at' => $result['record']->marked_at->format('g:i A'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        } catch (Throwable $e) {
            report($e);

            $userSafeMessages = [
                'We could not read that scan. Please try scanning again.',
                'This is not a valid attendance code. Please scan the QR code your instructor shows in class.',
                'This attendance code could not be verified. Ask your instructor for a fresh code.',
                'This attendance code is too old. Ask your instructor to show the current QR code.',
                'This attendance code does not match the active session. Scan the QR code your instructor is displaying now.',
                'This check-in is already closed. If you are in class, ask your instructor to start a new attendance session.',
                'This QR code has expired. Ask your instructor to refresh the attendance code.',
                'You are not enrolled in this class, so attendance cannot be recorded.',
                'Your attendance for this class is already recorded.',
                'This attendance code is no longer available. Ask your instructor to show the current QR code.',
                'Connect to the classroom network, then try again. If you need help, ask your instructor.',
                'Too many scan attempts. Please wait a moment and try again.',
            ];

            if (in_array($e->getMessage(), $userSafeMessages, true)) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'We could not record your attendance. Please try again in a moment.',
            ], 500);
        }
    }

    /**
     * Show attendance history
     */
    public function history()
    {
        $student = Auth::user();

        $records = $student->attendanceRecords()
            ->with([
                'attendanceSession.schedule' => fn ($q) => $q->withTrashed(),
                'attendanceSession.schedule.course' => fn ($q) => $q->withTrashed(),
                'attendanceSession.schedule.section' => fn ($q) => $q->withTrashed(),
            ])
            ->orderBy('marked_at', 'desc')
            ->paginate(20);

        return view('student.attendance.history', compact('records'));
    }
}
