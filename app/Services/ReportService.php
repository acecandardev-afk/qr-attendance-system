<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\User;
use App\Models\Section;
use App\Models\Schedule;
use App\Models\Course;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        $byCourse = $records->groupBy(function($record) {
            return $record->attendanceSession->schedule->course_id;
        })->map(function($courseRecords) {
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

        // Get all schedules for this section
        $schedulesQuery = Schedule::where('section_id', $sectionId)->with('course');
        
        if ($courseId) {
            $schedulesQuery->where('course_id', $courseId);
        }

        $schedules = $schedulesQuery->get();

        // Get all sessions for these schedules
        $sessionIds = AttendanceSession::whereIn('schedule_id', $schedules->pluck('id'))
            ->when($startDate, function($q) use ($startDate) {
                return $q->whereDate('started_at', '>=', $startDate);
            })
            ->when($endDate, function($q) use ($endDate) {
                return $q->whereDate('started_at', '<=', $endDate);
            })
            ->pluck('id');

        // Get attendance records
        $records = AttendanceRecord::whereIn('attendance_session_id', $sessionIds)
            ->with(['student', 'attendanceSession.schedule.course'])
            ->get();

        // Build student summary
        $studentSummary = $section->students->map(function($student) use ($records, $sessionIds) {
            $studentRecords = $records->where('student_id', $student->id);
            $totalSessions = $sessionIds->count();

            return [
                'student' => $student,
                'total_sessions' => $totalSessions,
                'attended' => $studentRecords->whereIn('status', ['present', 'late'])->count(),
                'present' => $studentRecords->where('status', 'present')->count(),
                'late' => $studentRecords->where('status', 'late')->count(),
                'absent' => $totalSessions - $studentRecords->count(),
                'excused' => $studentRecords->where('status', 'excused')->count(),
                'attendance_rate' => $totalSessions > 0
                    ? round(($studentRecords->whereIn('status', ['present', 'late'])->count() / $totalSessions) * 100, 2)
                    : 0,
            ];
        })->sortByDesc('attendance_rate');

        return [
            'section' => $section,
            'total_sessions' => $sessionIds->count(),
            'total_students' => $section->students->count(),
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

        // Group by course
        $byCourse = $sessions->groupBy(function ($session) {
            return $session->schedule?->course_id;
        })->filter(function ($courseSessions, $courseId) {
            return ! is_null($courseId);
        })->map(function ($courseSessions) {
            $totalRecords = $courseSessions->sum(function($session) {
                return $session->attendanceRecords->count();
            });

            return [
                'course' => $courseSessions->first()->schedule->course,
                'total_sessions' => $courseSessions->count(),
                'total_attendance_records' => $totalRecords,
                'average_attendance_per_session' => $courseSessions->count() > 0
                    ? round($totalRecords / $courseSessions->count(), 2)
                    : 0,
            ];
        });

        return [
            'faculty' => $faculty,
            'total_sessions' => $sessions->count(),
            'active_sessions' => $sessions->where('status', 'active')->count(),
            'closed_sessions' => $sessions->where('status', 'closed')->count(),
            'total_attendance_records' => $sessions->sum(function($session) {
                return $session->attendanceRecords->count();
            }),
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
            $query->whereHas('attendanceSession.schedule', function($q) use ($sectionId) {
                $q->where('section_id', $sectionId);
            });
        }

        $records = $query->get();

        // Group by date
        $byDate = $records->groupBy(function($record) {
            return $record->marked_at->format('Y-m-d');
        })->map(function($dateRecords, $date) {
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

        $filename = storage_path("app/reports/student_{$studentId}_attendance_" . time() . ".csv");
        
        if (!file_exists(dirname($filename))) {
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