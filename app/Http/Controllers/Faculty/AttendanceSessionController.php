<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Schedule;
use App\Services\AttendanceSessionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AttendanceSessionController extends Controller
{
    protected $sessionService;

    public function __construct(AttendanceSessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Display list of schedules for session creation
     */
    public function index()
    {
        $faculty = Auth::user();

        // Get today's schedules
        $todaySchedules = Schedule::byFaculty($faculty->id)
            ->today()
            ->active()
            ->with([
                'course' => fn ($q) => $q->withTrashed(),
                'section' => fn ($q) => $q->withTrashed(),
                'attendanceSessions' => function ($query) {
                    $query->where('started_at', '>=', Carbon::today())
                        ->orderBy('started_at', 'desc');
                },
            ])
            ->get();

        $sectionIds = Schedule::query()
            ->where('faculty_id', $faculty->id)
            ->where('status', 'active')
            ->distinct()
            ->pluck('section_id')
            ->filter()
            ->all();

        $pendingEnrollmentRequests = collect();
        if ($sectionIds !== []) {
            $pendingEnrollmentRequests = Enrollment::with([
                'student' => fn ($q) => $q->withTrashed(),
                'section' => fn ($q) => $q->withTrashed(),
                'schedules.course',
            ])
                ->pending()
                ->whereIn('section_id', $sectionIds)
                ->whereHas('schedules', fn ($q) => $q->where('faculty_id', $faculty->id))
                ->latest()
                ->get();
        }

        $flatSchedules = Schedule::byFaculty($faculty->id)
            ->active()
            ->with([
                'course' => fn ($q) => $q->withTrashed(),
                'section' => fn ($q) => $q->withTrashed(),
            ])
            ->orderByDayPattern()
            ->orderBy('start_time')
            ->get();

        $subjectCreateDepartments = ! $faculty->department_id
            ? Department::query()->orderBy('name')->get()
            : collect();

        $todaySchedulesPayload = $this->serializeTodaySchedulesPayload($todaySchedules);
        $todayDateLabel = Carbon::now()->format('l, F j, Y');

        return view('faculty.sessions.index', compact(
            'todaySchedulesPayload',
            'todayDateLabel',
            'pendingEnrollmentRequests',
            'flatSchedules',
            'subjectCreateDepartments',
        ));
    }

    /**
     * JSON for the My Subjects page: today's classes in app timezone (default Asia/Manila).
     */
    public function todayData()
    {
        $faculty = Auth::user();

        $todaySchedules = Schedule::byFaculty($faculty->id)
            ->today()
            ->active()
            ->with([
                'course' => fn ($q) => $q->withTrashed(),
                'section' => fn ($q) => $q->withTrashed(),
                'attendanceSessions' => function ($query) {
                    $query->where('started_at', '>=', Carbon::today())
                        ->orderBy('started_at', 'desc');
                },
            ])
            ->get();

        $now = Carbon::now();

        return response()
            ->json([
                'date_label' => $now->format('l, F j, Y'),
                'time_display' => $now->format('g:i A'),
                'timezone' => $now->timezoneName,
                'schedules' => $this->serializeTodaySchedulesPayload($todaySchedules),
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Schedule>  $todaySchedules
     * @return array<int, array<string, mixed>>
     */
    protected function serializeTodaySchedulesPayload($todaySchedules): array
    {
        return $todaySchedules->map(function (Schedule $schedule) {
            $activeSession = $schedule->attendanceSessions
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->first();
            $latestSession = $schedule->attendanceSessions->first();

            $courseName = $schedule->course?->name ?? 'Subject removed';
            $sectionName = $schedule->section?->name ?? 'Section removed';

            return [
                'id' => $schedule->id,
                'course_name' => $courseName,
                'section_name' => $sectionName,
                'time_range' => $schedule->time_range,
                'room' => $schedule->room ?? '',
                'sub_label' => "{$courseName} — {$sectionName}",
                'active_session_id' => $activeSession?->id,
                'active_session_url' => $activeSession ? route('faculty.sessions.show', $activeSession->id) : null,
                'edit_url' => route('faculty.subjects.edit', $schedule),
                'destroy_url' => route('faculty.subjects.destroy', $schedule),
                'latest_session' => $latestSession ? [
                    'started_at' => $latestSession->started_at->format('g:i A'),
                    'attendance_count' => $latestSession->attendance_count,
                ] : null,
            ];
        })->values()->all();
    }

    /**
     * Start a new attendance session
     */
    public function store(Request $request)
    {
        $request->validate([
            'schedule_id' => 'required|exists:schedules,id',
        ]);

        $faculty = Auth::user();
        $schedule = Schedule::findOrFail($request->schedule_id);

        // Verify faculty owns this schedule
        if ($schedule->faculty_id !== $faculty->id) {
            abort(403, 'You cannot start a session for this schedule.');
        }

        // Verify schedule is for today (optional - with tolerance)
        if (! $schedule->isToday()) {
            return back()->with('error', 'This class is not scheduled for today. Use today’s schedule to start attendance.');
        }

        try {
            $session = $this->sessionService->startSession($schedule, $faculty->id);

            return redirect()->route('faculty.sessions.show', $session->id)
                ->with('success', 'Attendance session started. Students can scan the QR code now.');
        } catch (\Throwable $e) {
            report($e);
            if ($e->getMessage() === 'FACULTY_SESSION_ALREADY_ACTIVE') {
                return back()->with('error', 'A session is already open for this class. Close it first, or open the existing session from your list.');
            }

            return back()->with('error', 'We could not start the attendance session. Please try again.');
        }
    }

    /**
     * Display active attendance session with QR code
     */
    public function show(AttendanceSession $session)
    {
        $faculty = Auth::user();

        // Verify faculty owns this session
        if ($session->faculty_id !== $faculty->id) {
            abort(403, 'You cannot view this session.');
        }

        $session->load([
            'schedule.course' => fn ($q) => $q->withTrashed(),
            'schedule.section' => fn ($q) => $q->withTrashed(),
            'attendanceRecords.student' => fn ($q) => $q->withTrashed(),
        ]);

        // QR code: serve via app route so it works even when public/storage is not symlinked to storage/app/public (common on Windows).
        $qrCodeUrl = null;
        if ($session->qr_code_path && Storage::disk('public')->exists($session->qr_code_path)) {
            $qrCodeUrl = route('faculty.sessions.qr-svg', $session, false);
        }
        if (! $qrCodeUrl) {
            $qrCodeUrl = $this->sessionService->getQrCodeDataUrl($session);
        }

        // Calculate remaining time
        $remainingSeconds = $session->remaining_time;

        return view('faculty.sessions.show', compact('session', 'qrCodeUrl', 'remainingSeconds'));
    }

    /**
     * Serve the stored QR SVG from the public disk (bypasses broken or missing public/storage symlink).
     */
    public function qrSvg(AttendanceSession $session)
    {
        $faculty = Auth::user();

        if ($session->faculty_id !== $faculty->id) {
            abort(403, 'You cannot view this QR code.');
        }

        if (! $session->qr_code_path || ! Storage::disk('public')->exists($session->qr_code_path)) {
            abort(404);
        }

        return Storage::disk('public')->response($session->qr_code_path, 'attendance-qr.svg', [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => 'inline; filename="attendance-qr.svg"',
            'Cache-Control' => 'private, max-age=30',
        ]);
    }

    /**
     * Close an attendance session manually
     */
    public function close(AttendanceSession $session)
    {
        $faculty = Auth::user();

        // Verify faculty owns this session
        if ($session->faculty_id !== $faculty->id) {
            abort(403, 'You cannot close this session.');
        }

        if ($session->status !== 'active') {
            return back()->with('error', 'This session is already closed or has expired.');
        }

        $this->sessionService->closeSession($session);

        return redirect()->route('faculty.sessions.index')
            ->with('success', 'Attendance session closed.');
    }

    /**
     * API endpoint to get session status (for auto-refresh)
     */
    public function status(AttendanceSession $session)
    {
        $faculty = Auth::user();

        // Verify faculty owns this session
        if ($session->faculty_id !== $faculty->id) {
            abort(403, 'You cannot view this session.');
        }

        return response()->json([
            'status' => $session->status,
            'remaining_time' => $session->remaining_time,
            'is_expired' => $session->isExpired(),
            'enrolled_count' => $session->enrolled_count,
            'attendance_count' => $session->attendance_count,
            'present_count' => $session->present_count,
            'late_count' => $session->late_count,
            'absent_count' => $session->absent_count,
        ]);
    }
}
