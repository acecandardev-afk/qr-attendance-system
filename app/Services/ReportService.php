<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Get attendance summary for a student
     */
    public function getStudentAttendanceSummary(int $studentId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = AttendanceRecord::where('student_id', $studentId)
            ->with(['attendanceSession.schedule.course']);

        if ($startDate) {
            $query->whereDate('marked_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('marked_at', '<=', $endDate);
        }

        $records = $query->get();

        // Group by course
        $byCourse = $records->groupBy(function ($record) {
            return $record->attendanceSession->schedule->course_id;
        })->map(function ($courseRecords) {
            return [
                'course' => $courseRecords->first()->attendanceSession->schedule->course,
                'total' => $courseRecords->count(),
                'present' => $courseRecords->where('status', 'present')->count(),
                'late' => $courseRecords->where('status', 'late')->count(),
                'absent' => $courseRecords->where('status', 'absent')->count(),
                'excused' => $courseRecords->where('status', 'excused')->count(),
                'attendance_rate' => $courseRecords->count() > 0
                    ? round(($courseRecords->whereIn('status', ['present', 'late'])->count() / $courseRecords->count()) * 100, 2)
                    : 0,
            ];
        });

        return [
            'total_records' => $records->count(),
            'present' => $records->where('status', 'present')->count(),
            'late' => $records->where('status', 'late')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'excused' => $records->where('status', 'excused')->count(),
            'overall_attendance_rate' => $records->count() > 0
                ? round(($records->whereIn('status', ['present', 'late'])->count() / $records->count()) * 100, 2)
                : 0,
            'by_course' => $byCourse,
            'recent_records' => $records->sortByDesc('marked_at')->take(20),
        ];
    }

    /**
     * Get attendance report for a section
     */
    public function getSectionAttendanceReport(int $sectionId, ?int $courseId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $section = Section::with('students')->findOrFail($sectionId);

        [$schedules, $sessions] = $this->sectionReportSchedulesAndSessions($sectionId, $courseId, $startDate, $endDate);

        if ($courseId) {
            $studentIds = collect();
            foreach ($schedules as $sch) {
                $studentIds = $studentIds->merge(Enrollment::eligibleForSchedule($sch)->pluck('student_id'));
            }
            $students = User::query()
                ->whereIn('id', $studentIds->unique()->filter())
                ->where('role', 'student')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
        } else {
            $students = $section->students;
        }

        $sessionIds = $sessions->pluck('id');
        $records = AttendanceRecord::whereIn('attendance_session_id', $sessionIds)
            ->with(['student', 'attendanceSession.schedule.course'])
            ->get()
            ->groupBy('student_id');

        $statsSvc = app(AttendanceSessionStatisticsService::class);

        $studentSummary = $students->map(function ($student) use ($sessions, $records, $statsSvc) {
            $studentRecords = $records->get($student->id, collect())->keyBy('attendance_session_id');
            $totalSessions = $sessions->count();
            $present = 0;
            $late = 0;
            $absent = 0;
            $excused = 0;
            foreach ($sessions as $session) {
                $rec = $studentRecords->get($session->id);
                if ($rec) {
                    match ($rec->status) {
                        'present' => $present++,
                        'late' => $late++,
                        'absent' => $absent++,
                        'excused' => $excused++,
                        default => null,
                    };

                    continue;
                }
                if ($statsSvc->statusForExportWithoutRecord($session) === 'absent') {
                    $absent++;
                }
            }
            $attended = $present + $late + $excused;

            return [
                'student' => $student,
                'total_sessions' => $totalSessions,
                'attended' => $attended,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'excused' => $excused,
                'attendance_rate' => $totalSessions > 0
                    ? round(($attended / $totalSessions) * 100, 2)
                    : 0,
            ];
        })->sortByDesc('attendance_rate');

        return [
            'section' => $section,
            'total_sessions' => $sessions->count(),
            'total_students' => $students->count(),
            'student_summary' => $studentSummary,
            'overall_stats' => [
                'average_attendance_rate' => $studentSummary->avg('attendance_rate'),
                'total_present' => $studentSummary->sum('present'),
                'total_late' => $studentSummary->sum('late'),
                'total_absent' => $studentSummary->sum('absent'),
            ],
        ];
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Database\Eloquent\Collection}
     */
    protected function sectionReportSchedulesAndSessions(int $sectionId, ?int $courseId, ?string $startDate, ?string $endDate): array
    {
        $schedulesQuery = Schedule::where('section_id', $sectionId)->with('course');
        if ($courseId) {
            $schedulesQuery->where('course_id', $courseId);
        }
        $schedules = $schedulesQuery->get();

        $sessions = AttendanceSession::whereIn('schedule_id', $schedules->pluck('id'))
            ->when($startDate, fn ($q) => $q->whereDate('started_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('started_at', '<=', $endDate))
            ->with([
                'schedule.course' => fn ($q) => $q->withTrashed(),
                'schedule.section' => fn ($q) => $q->withTrashed(),
                'faculty',
            ])
            ->orderBy('started_at')
            ->get();

        return [$schedules, $sessions];
    }

    /**
     * One row per class session × enrolled student (for CSV export).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getSectionAttendanceDetailRows(int $sectionId, ?int $courseId, ?string $startDate, ?string $endDate): Collection
    {
        [, $sessions] = $this->sectionReportSchedulesAndSessions($sectionId, $courseId, $startDate, $endDate);
        $statsSvc = app(AttendanceSessionStatisticsService::class);

        return $this->buildSessionStudentDetailRows($sessions, $statsSvc);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getFacultyAttendanceDetailRows(int $facultyId, ?string $startDate, ?string $endDate): Collection
    {
        $sessions = AttendanceSession::where('faculty_id', $facultyId)
            ->whereHas('schedule')
            ->when($startDate, fn ($q) => $q->whereDate('started_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('started_at', '<=', $endDate))
            ->with([
                'schedule.course' => fn ($q) => $q->withTrashed(),
                'schedule.section' => fn ($q) => $q->withTrashed(),
                'attendanceRecords',
                'faculty',
            ])
            ->orderBy('started_at')
            ->get()
            ->filter(fn ($session) => $session->schedule !== null);

        $statsSvc = app(AttendanceSessionStatisticsService::class);

        return $this->buildSessionStudentDetailRows($sessions, $statsSvc);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, AttendanceSession>  $sessions
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildSessionStudentDetailRows($sessions, AttendanceSessionStatisticsService $statsSvc): Collection
    {
        $rows = collect();

        foreach ($sessions as $session) {
            $schedule = $session->schedule;
            if (! $schedule) {
                continue;
            }

            $session->loadMissing('attendanceRecords');

            $studentIds = Enrollment::eligibleForSchedule($schedule)->pluck('student_id')->unique()->values();
            if ($studentIds->isEmpty()) {
                continue;
            }

            $users = User::query()
                ->whereIn('id', $studentIds)
                ->where('role', 'student')
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get()
                ->keyBy('id');

            $records = $session->attendanceRecords->keyBy('student_id');

            $course = $schedule->course;
            $section = $schedule->section;

            foreach ($studentIds as $studentId) {
                $student = $users->get($studentId);
                if (! $student) {
                    continue;
                }
                $rec = $records->get($studentId);
                if ($rec) {
                    $status = strtolower((string) $rec->status);
                } else {
                    $fallback = $statsSvc->statusForExportWithoutRecord($session);
                    $status = $fallback === '' ? '' : $fallback;
                }

                $rows->push([
                    'session_date' => $session->started_at->format('Y-m-d'),
                    'session_time' => $session->started_at->format('H:i:s'),
                    'session_started_at' => $session->started_at->toIso8601String(),
                    'course_code' => $course->code ?? '',
                    'course_name' => $course->name ?? '',
                    'section_name' => $section->name ?? '',
                    'student_user_id' => $student->user_id ?? '',
                    'student_name' => $student->full_name ?? '',
                    'status' => $status,
                ]);
            }
        }

        return $rows;
    }

    /**
     * Get faculty attendance report
     */
    public function getFacultyAttendanceReport(int $facultyId, ?string $startDate = null, ?string $endDate = null): array
    {
        $faculty = User::findOrFail($facultyId);

        $sessionsQuery = AttendanceSession::where('faculty_id', $facultyId)
            ->whereHas('schedule')
            ->with([
                'schedule.course' => fn ($q) => $q->withTrashed(),
                'schedule.section' => fn ($q) => $q->withTrashed(),
                'attendanceRecords',
            ]);

        if ($startDate) {
            $sessionsQuery->whereDate('started_at', '>=', $startDate);
        }

        if ($endDate) {
            $sessionsQuery->whereDate('started_at', '<=', $endDate);
        }

        $sessions = $sessionsQuery->get()->filter(function ($session) {
            return $session->schedule !== null && $session->schedule->course !== null;
        })->values();

        $totalPresent = (int) $sessions->sum(fn ($session) => $session->attendanceRecords->where('status', 'present')->count());
        $totalLate = (int) $sessions->sum(fn ($session) => $session->attendanceRecords->where('status', 'late')->count());
        $totalAbsent = (int) $sessions->sum(fn ($session) => $session->attendanceRecords->where('status', 'absent')->count());

        // Group by course
        $byCourse = $sessions->groupBy(function ($session) {
            return $session->schedule?->course_id;
        })->filter(function ($courseSessions, $courseId) {
            return ! is_null($courseId);
        })->map(function ($courseSessions) {
            $totalRecords = $courseSessions->sum(function ($session) {
                return $session->attendanceRecords->count();
            });

            return [
                'course' => $courseSessions->first()->schedule->course,
                'total_sessions' => $courseSessions->count(),
                'total_attendance_records' => $totalRecords,
                'average_attendance_per_session' => $courseSessions->count() > 0
                    ? round($totalRecords / $courseSessions->count(), 2)
                    : 0,
                'present' => (int) $courseSessions->sum(fn ($s) => $s->attendanceRecords->where('status', 'present')->count()),
                'late' => (int) $courseSessions->sum(fn ($s) => $s->attendanceRecords->where('status', 'late')->count()),
                'absent' => (int) $courseSessions->sum(fn ($s) => $s->attendanceRecords->where('status', 'absent')->count()),
            ];
        });

        return [
            'faculty' => $faculty,
            'total_sessions' => $sessions->count(),
            'active_sessions' => $sessions->where('status', 'active')->count(),
            'closed_sessions' => $sessions->where('status', 'closed')->count(),
            'expired_sessions' => $sessions->where('status', 'expired')->count(),
            'total_attendance_records' => $sessions->sum(function ($session) {
                return $session->attendanceRecords->count();
            }),
            'total_present' => $totalPresent,
            'total_late' => $totalLate,
            'total_absent' => $totalAbsent,
            'by_course' => $byCourse,
            'recent_sessions' => $sessions->sortByDesc('started_at')->take(10),
        ];
    }

    /**
     * Get daily attendance statistics
     */
    public function getDailyAttendanceStats(?string $date = null): array
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();

        $sessions = AttendanceSession::whereDate('started_at', $date)
            ->with([
                'schedule.course' => fn ($q) => $q->withTrashed(),
                'schedule.section' => fn ($q) => $q->withTrashed(),
                'faculty' => fn ($q) => $q->withTrashed(),
                'attendanceRecords',
            ])
            ->get();

        $records = AttendanceRecord::whereDate('marked_at', $date)->get();

        return [
            'date' => $date->format('Y-m-d'),
            'total_sessions' => $sessions->count(),
            'total_attendance_marked' => $records->count(),
            'present' => $records->where('status', 'present')->count(),
            'late' => $records->where('status', 'late')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'excused' => $records->where('status', 'excused')->count(),
            'sessions' => $sessions,
        ];
    }

    public function getAttendanceStatsRange(?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : null;

        $sessionsQuery = AttendanceSession::query()
            ->with([
                'schedule.course' => fn ($q) => $q->withTrashed(),
                'schedule.section' => fn ($q) => $q->withTrashed(),
                'faculty' => fn ($q) => $q->withTrashed(),
                'attendanceRecords',
            ]);

        if ($start) {
            $sessionsQuery->where('started_at', '>=', $start);
        }
        if ($end) {
            $sessionsQuery->where('started_at', '<=', $end);
        }

        $sessions = $sessionsQuery->get();

        $recordsQuery = AttendanceRecord::query();
        if ($start) {
            $recordsQuery->where('marked_at', '>=', $start);
        }
        if ($end) {
            $recordsQuery->where('marked_at', '<=', $end);
        }
        $records = $recordsQuery->get();

        return [
            'start_date' => $start ? $start->format('Y-m-d') : null,
            'end_date' => $end ? $end->format('Y-m-d') : null,
            'total_sessions' => $sessions->count(),
            'total_attendance_marked' => $records->count(),
            'present' => $records->where('status', 'present')->count(),
            'late' => $records->where('status', 'late')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'excused' => $records->where('status', 'excused')->count(),
            'sessions' => $sessions,
        ];
    }

    /**
     * Get attendance trends over time
     */
    public function getAttendanceTrends(?string $startDate = null, ?string $endDate = null, ?int $sectionId = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->subDays(30);
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        $query = AttendanceRecord::whereBetween('marked_at', [$startDate, $endDate])
            ->with('attendanceSession.schedule.section');

        if ($sectionId) {
            $query->whereHas('attendanceSession.schedule', function ($q) use ($sectionId) {
                $q->where('section_id', $sectionId);
            });
        }

        $records = $query->get();

        // Group by date
        $byDate = $records->groupBy(function ($record) {
            return $record->marked_at->format('Y-m-d');
        })->map(function ($dateRecords, $date) {
            return [
                'date' => $date,
                'total' => $dateRecords->count(),
                'present' => $dateRecords->where('status', 'present')->count(),
                'late' => $dateRecords->where('status', 'late')->count(),
                'absent' => $dateRecords->where('status', 'absent')->count(),
                'attendance_rate' => $dateRecords->count() > 0
                    ? round(($dateRecords->whereIn('status', ['present', 'late'])->count() / $dateRecords->count()) * 100, 2)
                    : 0,
            ];
        })->sortBy('date');

        return [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'trends' => $byDate->values(),
        ];
    }

    /**
     * Export student attendance to CSV
     */
    public function exportStudentAttendanceCSV(int $studentId): string
    {
        $student = User::findOrFail($studentId);
        $records = AttendanceRecord::where('student_id', $studentId)
            ->with([
                'attendanceSession.schedule.course' => fn ($q) => $q->withTrashed(),
                'attendanceSession.schedule.section' => fn ($q) => $q->withTrashed(),
            ])
            ->orderBy('marked_at', 'desc')
            ->get();

        $filename = storage_path("app/reports/student_{$studentId}_attendance_".time().'.csv');

        if (! file_exists(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }

        $file = fopen($filename, 'w');

        // Header
        fputcsv($file, [
            'Date',
            'Time',
            'Course',
            'Section',
            'Status',
            'IP Address',
        ]);

        // Data
        foreach ($records as $record) {
            $courseName = $record->attendanceSession?->schedule?->course?->name ?? '';
            $sectionName = $record->attendanceSession?->schedule?->section?->name ?? '';
            fputcsv($file, [
                $record->marked_at->format('Y-m-d'),
                $record->marked_at->format('H:i:s'),
                $courseName,
                $sectionName,
                ucfirst($record->status),
                $record->ip_address,
            ]);
        }

        fclose($file);

        return $filename;
    }
}
