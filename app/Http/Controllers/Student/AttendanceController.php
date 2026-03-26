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
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'course' => $result['session']->schedule->course->name,
                    'section' => $result['session']->schedule->section->name,
                    'marked_at' => $result['record']->marked_at->format('g:i A'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        } catch (Throwable $e) {
            report($e);

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
            ->with(['attendanceSession.schedule.course', 'attendanceSession.schedule.section'])
            ->orderBy('marked_at', 'desc')
            ->paginate(20);

        return view('student.attendance.history', compact('records'));
    }
}
