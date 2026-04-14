<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\User;
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

        // Get all schedules for the faculty
        $allSchedules = Schedule::byFaculty($faculty->id)
            ->active()
            ->with([
                'course' => fn ($q) => $q->withTrashed(),
                'section' => fn ($q) => $q->withTrashed(),
            ])
            ->orderByDayPattern()
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        $adHocTemplates = Schedule::byFaculty($faculty->id)
            ->active()
            ->with([
                'course' => fn ($q) => $q->withTrashed(),
                'section' => fn ($q) => $q->withTrashed(),
            ])
            ->orderBy('course_id')
            ->get();

        return view('faculty.sessions.index', compact('todaySchedules', 'allSchedules', 'adHocTemplates'));
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
            return back()->with('error', 'This class is not scheduled for today. Pick today’s schedule or start an emergency class.');
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
     * Start an Emergency class attendance (without requiring today's predefined schedule slot).
     */
    public function storeAdHoc(Request $request)
    {
        $validated = $request->validate([
            'template_schedule_id' => ['required', 'integer', 'exists:schedules,id'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:180'],
            'room' => ['nullable', 'string', 'max:255'],
        ]);

        $faculty = Auth::user();
        $template = Schedule::with('course', 'section')->findOrFail($validated['template_schedule_id']);

        if ($template->faculty_id !== $faculty->id) {
            abort(403, 'You cannot use this class template.');
        }

        try {
            $start = now();
            $end = now()->copy()->addMinutes((int) $validated['duration_minutes']);

            $adHocSchedule = Schedule::create([
                'course_id' => $template->course_id,
                'section_id' => $template->section_id,
                'faculty_id' => $faculty->id,
                'day_of_week' => $template->day_of_week,
                'start_time' => $start->format('H:i:s'),
                'end_time' => $end->format('H:i:s'),
                'room' => $validated['room'] ?? $template->room,
                'status' => 'active',
            ]);

            $session = $this->sessionService->startSession($adHocSchedule, $faculty->id);

            return redirect()->route('faculty.sessions.show', $session->id)
                ->with('success', 'Emergency class attendance started. Students can scan the QR code now.');
        } catch (\Throwable $e) {
            report($e);
            if ($e->getMessage() === 'FACULTY_SESSION_ALREADY_ACTIVE') {
                return back()->with('error', 'A session is already open for this class. Close it first, or open the existing session from your list.');
            }

            return back()->with('error', 'We could not start the emergency class. Please try again.');
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

        // Students enrolled for this schedule (or whole section if their enrollment has no schedule picks)
        $students = collect();
        if ($session->schedule->section) {
            $studentIds = Enrollment::query()
                ->eligibleForSchedule($session->schedule)
                ->pluck('student_id')
                ->unique()
                ->values();

            $students = User::query()
                ->whereIn('id', $studentIds)
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        }

        // QR code: use stored file if present, otherwise generate inline so it always displays
        $qrCodeUrl = null;
        if ($session->qr_code_path && Storage::disk('public')->exists($session->qr_code_path)) {
            $qrCodeUrl = asset('storage/'.$session->qr_code_path);
        }
        if (! $qrCodeUrl) {
            $qrCodeUrl = $this->sessionService->getQrCodeDataUrl($session);
        }

        // Calculate remaining time
        $remainingSeconds = $session->remaining_time;

        return view('faculty.sessions.show', compact('session', 'qrCodeUrl', 'remainingSeconds', 'students'));
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
            'attendance_count' => $session->attendance_count,
            'present_count' => $session->present_count,
            'late_count' => $session->late_count,
        ]);
    }

    /**
     * Manually update a student's attendance for a session.
     * Allows faculty to mark a student present, late, excused, or absent (clear record).
     */
    public function updateManualAttendance(Request $request, AttendanceSession $session)
    {
        $faculty = Auth::user();

        // Verify faculty owns this session
        if ($session->faculty_id !== $faculty->id) {
            abort(403, 'You cannot change attendance for this session.');
        }

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'in:present,late,absent,excused'],
        ]);

        $studentId = $validated['student_id'];
        $status = $validated['status'] ?? null;

        $session->loadMissing('schedule');
        $eligible = Enrollment::query()
            ->where('student_id', $studentId)
            ->eligibleForSchedule($session->schedule)
            ->exists();
        if (! $eligible) {
            return back()->with('error', 'That student is not enrolled for this class schedule.');
        }

        $record = AttendanceRecord::where('attendance_session_id', $session->id)
            ->where('student_id', $studentId)
            ->first();

        // Treat "absent" or null as "no record" so reports continue to derive absences
        if (! $status || $status === 'absent') {
            if ($record) {
                $record->delete();
            }
        } else {
            if ($record) {
                $record->update([
                    'status' => $status,
                    'marked_at' => now(),
                ]);
            } else {
                AttendanceRecord::create([
                    'attendance_session_id' => $session->id,
                    'student_id' => $studentId,
                    'status' => $status,
                    'marked_at' => now(),
                    'ip_address' => $request->ip(),
                    'network_identifier' => null,
                ]);
            }
        }

        return redirect()
            ->route('faculty.sessions.show', $session->id)
            ->with('success', 'Attendance updated.');
    }

    /**
     * Bulk update attendance for all or unmarked students.
     */
    public function bulkManualAttendance(Request $request, AttendanceSession $session)
    {
        $faculty = Auth::user();

        if ($session->faculty_id !== $faculty->id) {
            abort(403, 'You cannot update attendance for this session.');
        }

        $validated = $request->validate([
            'target' => ['required', 'in:all,unmarked'],
            'status' => ['required', 'in:present,late,excused,absent'],
        ]);

        $students = $session->schedule->section
            ? Enrollment::query()
                ->eligibleForSchedule($session->schedule)
                ->pluck('student_id')
                ->unique()
                ->values()
            : collect();

        if ($students->isEmpty()) {
            return back()->with('error', 'There are no enrolled students to update for this section.');
        }

        $existingRecords = AttendanceRecord::where('attendance_session_id', $session->id)
            ->whereIn('student_id', $students)
            ->get()
            ->keyBy('student_id');

        $targetIds = $validated['target'] === 'unmarked'
            ? $students->filter(fn ($id) => ! $existingRecords->has($id))->values()
            : $students;

        foreach ($targetIds as $studentId) {
            $record = $existingRecords->get($studentId);

            if ($validated['status'] === 'absent') {
                if ($record) {
                    $record->delete();
                }

                continue;
            }

            if ($record) {
                $record->update([
                    'status' => $validated['status'],
                    'marked_at' => now(),
                ]);
            } else {
                AttendanceRecord::create([
                    'attendance_session_id' => $session->id,
                    'student_id' => $studentId,
                    'status' => $validated['status'],
                    'marked_at' => now(),
                    'ip_address' => $request->ip(),
                    'network_identifier' => null,
                ]);
            }
        }

        return redirect()
            ->route('faculty.sessions.show', $session->id)
            ->with('success', 'Bulk attendance update finished.');
    }
}
