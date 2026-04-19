<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'faculty') {
            return $this->facultyDashboard($user);
        }

        return match ($user->role) {
            'admin' => view('dashboard.admin', compact('user')),
            'student' => view('dashboard.student', $this->studentDashboardData($user)),
            default => abort(403, 'You do not have permission to open this page.'),
        };
    }

    /**
     * Search and request to join classes (student).
     */
    public function studentBrowseClasses()
    {
        $user = Auth::user();
        abort_unless($user->isStudent(), 403);

        return view('student.browse-classes', [
            'joinableCards' => $this->joinableClassCardsForStudent($user),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function joinableClassCardsForStudent(User $user): array
    {
        $openSchedules = Schedule::query()
            ->where('status', 'active')
            ->with([
                'course',
                'section' => fn ($q) => $q->withTrashed(),
                'faculty',
            ])
            ->orderByDayPattern()
            ->orderBy('start_time')
            ->get();

        $scheduleJoinStatus = [];
        $active = $user->enrollments()
            ->whereIn('status', [Enrollment::STATUS_PENDING, Enrollment::STATUS_ENROLLED])
            ->with(['schedules:id'])
            ->get();

        foreach ($active as $enrollment) {
            foreach ($enrollment->schedules as $sch) {
                $scheduleJoinStatus[(int) $sch->id] = $enrollment->status;
            }
        }

        return $openSchedules->map(function (Schedule $s) use ($scheduleJoinStatus) {
            $subject = (string) ($s->course?->code ?? 'Subject');
            $section = (string) ($s->section?->name ?? '');
            $instructor = (string) ($s->faculty?->full_name_without_middle ?? '');
            $when = $s->day_of_week.' · '.$s->time_range;

            return [
                'id' => $s->id,
                'subject' => $subject,
                'section' => $section,
                'when' => $when,
                'instructor' => $instructor,
                'search' => mb_strtolower($subject.' '.$section.' '.$when.' '.$instructor),
                'status' => $scheduleJoinStatus[$s->id] ?? null,
            ];
        })->values()->all();
    }

    /**
     * @return array{user: User, myEnrollments: \Illuminate\Support\Collection}
     */
    private function studentDashboardData(User $user): array
    {
        $myEnrollments = $user->enrollments()
            ->whereIn('status', [Enrollment::STATUS_PENDING, Enrollment::STATUS_ENROLLED])
            ->with([
                'section' => fn ($q) => $q->withTrashed(),
                'section.department' => fn ($q) => $q->withTrashed(),
                'schedules.course',
            ])
            ->orderByDesc('updated_at')
            ->get();

        return [
            'user' => $user,
            'myEnrollments' => $myEnrollments,
        ];
    }

    private function facultyDashboard($user)
    {
        $scheduleIds = $user->facultySchedules()->active()->pluck('id');

        // Stat cards
        $totalSchedules = $scheduleIds->count();
        $activeSessionsToday = AttendanceSession::whereIn('schedule_id', $scheduleIds)
            ->where('status', 'active')
            ->whereDate('started_at', today())
            ->count();
        $sectionIds = $user->facultySchedules()->active()->distinct('section_id')->pluck('section_id');
        $totalStudents = Enrollment::whereIn('section_id', $sectionIds)
            ->where('status', 'enrolled')
            ->distinct('student_id')
            ->count('student_id');

        // Daily attendance chart — last 14 days
        $days = collect();
        $present = collect();
        $late = collect();
        $absent = collect();

        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $days->push($date->format('M d'));

            $sessionIds = AttendanceSession::whereIn('schedule_id', $scheduleIds)
                ->whereDate('started_at', $date)
                ->pluck('id');

            $records = AttendanceRecord::whereIn('attendance_session_id', $sessionIds)->get();

            $present->push($records->where('status', 'present')->count());
            $late->push($records->where('status', 'late')->count());
            $absent->push($records->where('status', 'absent')->count());
        }

        return view('dashboard.faculty', compact(
            'user',
            'totalSchedules',
            'activeSessionsToday',
            'totalStudents',
            'days',
            'present',
            'late',
            'absent'
        ));
    }
}
