<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Reports dashboard
     */
    public function index(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $dailyStats = $this->reportService->getAttendanceStatsRange($request->start_date, $request->end_date);
        } else {
            $dailyStats = $this->reportService->getDailyAttendanceStats($request->date);
        }

        return view('admin.reports.index', compact('dailyStats'));
    }

    /**
     * Printable summary for a single day (opens browser print dialog).
     */
    public function dailyPrint(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
        ]);

        $dailyStats = $this->reportService->getDailyAttendanceStats($request->date);

        return view('admin.reports.daily-print', compact('dailyStats'));
    }
    public function exportDailyCSV(Request $request): StreamedResponse
    {
        $request->validate([
            'date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $dailyStats = $this->reportService->getAttendanceStatsRange($request->start_date, $request->end_date);
            $start = $dailyStats['start_date'] ?? 'all';
            $end = $dailyStats['end_date'] ?? 'all';
            $filename = "attendance_{$start}_to_{$end}.csv";
        } else {
            $dailyStats = $this->reportService->getDailyAttendanceStats($request->date);
            $date = $dailyStats['date'] ?? Carbon::now()->format('Y-m-d');
            $filename = "daily_attendance_{$date}.csv";
        }

        return response()->streamDownload(function () use ($dailyStats) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            if (!empty($dailyStats['date'])) {
                fputcsv($out, ['Date', $dailyStats['date'] ?? '']);
            } else {
                fputcsv($out, ['Start Date', $dailyStats['start_date'] ?? '']);
                fputcsv($out, ['End Date', $dailyStats['end_date'] ?? '']);
            }
            fputcsv($out, []);
            fputcsv($out, ['Sessions', $dailyStats['total_sessions'] ?? 0]);
            fputcsv($out, ['Scans recorded', $dailyStats['total_attendance_marked'] ?? 0]);
            fputcsv($out, ['Present', $dailyStats['present'] ?? 0]);
            fputcsv($out, ['Late', $dailyStats['late'] ?? 0]);
            fputcsv($out, ['Absent', $dailyStats['absent'] ?? 0]);
            fputcsv($out, []);

            fputcsv($out, ['Subject Code', 'Subject', 'Section', 'Faculty', 'Started At', 'Status', 'Attendance']);
            foreach (($dailyStats['sessions'] ?? []) as $session) {
                fputcsv($out, [
                    $session->schedule->course->code ?? '',
                    $session->schedule->course->name ?? '',
                    $session->schedule->section->name ?? '',
                    $session->faculty->full_name ?? '',
                    optional($session->started_at)->format('H:i:s'),
                    $session->status ?? '',
                    $session->attendanceRecords->count(),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Student attendance report
     */
    public function studentReport(Request $request)
    {
        if ($request->filled('student_id')) {
            $request->validate([
                'student_id' => 'required|integer|exists:users,id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);
        }

        $students = User::students()->active()->get();
        $data = null;

        if ($request->filled('student_id')) {
            $data = $this->reportService->getStudentAttendanceSummary(
                $request->student_id,
                $request->start_date,
                $request->end_date
            );
            $data['student'] = User::findOrFail($request->student_id);
        }

        return view('admin.reports.student', compact('students', 'data'));
    }

    /**
     * Section attendance report
     */
    public function sectionReport(Request $request)
    {
        if ($request->filled('section_id') || $request->filled('course_id')) {
            $request->validate([
                'section_id' => 'required|integer|exists:sections,id',
                'course_id' => 'nullable|integer|exists:courses,id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);
        }

        $sections = Section::active()->with('department')->get();
        $courses = [];
        $data = null;

        if ($request->filled('section_id')) {
            $section = Section::findOrFail($request->section_id);
            $courses = Course::whereHas('schedules', function ($q) use ($request) {
                $q->where('section_id', $request->section_id);
            })->get();

            $data = $this->reportService->getSectionAttendanceReport(
                $request->section_id,
                $request->course_id,
                $request->start_date,
                $request->end_date
            );
        }

        return view('admin.reports.section', compact('sections', 'courses', 'data'));
    }
    public function exportSectionCSV(Request $request): StreamedResponse
    {
        $request->validate([
            'section_id' => 'required|integer|exists:sections,id',
            'course_id' => 'nullable|integer|exists:courses,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $data = $this->reportService->getSectionAttendanceReport(
            (int) $request->section_id,
            $request->course_id ? (int) $request->course_id : null,
            $request->start_date,
            $request->end_date
        );

        $sectionName = $data['section']->name ?? 'section';
        $filename = 'section_attendance_' . preg_replace('/[^A-Za-z0-9\-_]+/', '_', $sectionName) . '_' . Carbon::now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($data, $request) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Section', $data['section']->name ?? '']);
            fputcsv($out, ['Start Date', (string) ($request->start_date ?? '')]);
            fputcsv($out, ['End Date', (string) ($request->end_date ?? '')]);
            fputcsv($out, []);
            fputcsv($out, ['Total Sessions', $data['total_sessions'] ?? 0]);
            fputcsv($out, ['Total Students', $data['total_students'] ?? 0]);
            fputcsv($out, ['Average Attendance Rate', round($data['overall_stats']['average_attendance_rate'] ?? 0, 2) . '%']);
            fputcsv($out, []);

            fputcsv($out, ['Student ID', 'Name', 'Sessions', 'Present', 'Late', 'Absent', 'Attendance Rate']);
            foreach (($data['student_summary'] ?? []) as $row) {
                fputcsv($out, [
                    $row['student']->user_id ?? '',
                    $row['student']->full_name ?? '',
                    $row['total_sessions'] ?? 0,
                    $row['present'] ?? 0,
                    $row['late'] ?? 0,
                    $row['absent'] ?? 0,
                    ($row['attendance_rate'] ?? 0) . '%',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Faculty attendance report
     */
    public function facultyReport(Request $request)
    {
        if ($request->filled('faculty_id')) {
            $request->validate([
                'faculty_id' => 'required|integer|exists:users,id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);
        }

        $faculty = User::faculty()->active()->get();
        $data = null;

        if ($request->filled('faculty_id')) {
            $data = $this->reportService->getFacultyAttendanceReport(
                $request->faculty_id,
                $request->start_date,
                $request->end_date
            );
        }

        return view('admin.reports.faculty', compact('faculty', 'data'));
    }
    public function exportFacultyCSV(Request $request): StreamedResponse
    {
        $request->validate([
            'faculty_id' => 'required|integer|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $data = $this->reportService->getFacultyAttendanceReport(
            (int) $request->faculty_id,
            $request->start_date,
            $request->end_date
        );

        $facultyName = $data['faculty']->full_name_without_middle ?? $data['faculty']->full_name ?? 'faculty';
        $filename = 'faculty_attendance_' . preg_replace('/[^A-Za-z0-9\-_]+/', '_', $facultyName) . '_' . Carbon::now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($data, $request) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Faculty', $data['faculty']->full_name ?? '']);
            fputcsv($out, ['Start Date', (string) ($request->start_date ?? '')]);
            fputcsv($out, ['End Date', (string) ($request->end_date ?? '')]);
            fputcsv($out, []);
            fputcsv($out, ['Total Sessions', $data['total_sessions'] ?? 0]);
            fputcsv($out, ['Active Sessions', $data['active_sessions'] ?? 0]);
            fputcsv($out, ['Closed Sessions', $data['closed_sessions'] ?? 0]);
            fputcsv($out, ['Total Attendance Records', $data['total_attendance_records'] ?? 0]);
            fputcsv($out, []);

            fputcsv($out, ['Course Code', 'Course', 'Total Sessions', 'Total Attendance', 'Avg per Session']);
            foreach (($data['by_course'] ?? collect()) as $courseData) {
                $course = $courseData['course'] ?? null;
                fputcsv($out, [
                    $course->code ?? '',
                    $course->name ?? '',
                    $courseData['total_sessions'] ?? 0,
                    $courseData['total_attendance_records'] ?? 0,
                    $courseData['average_attendance_per_session'] ?? 0,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Attendance trends report
     */
    public function trendsReport(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'section_id' => 'nullable|integer|exists:sections,id',
        ]);

        $sections = Section::active()->get();

        $data = $this->reportService->getAttendanceTrends(
            $request->start_date ?? Carbon::now()->subDays(30)->format('Y-m-d'),
            $request->end_date ?? Carbon::now()->format('Y-m-d'),
            $request->section_id
        );

        return view('admin.reports.trends', compact('sections', 'data'));
    }
    public function exportTrendsCSV(Request $request): StreamedResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'section_id' => 'nullable|integer|exists:sections,id',
        ]);

        $data = $this->reportService->getAttendanceTrends(
            $request->start_date ?? Carbon::now()->subDays(30)->format('Y-m-d'),
            $request->end_date ?? Carbon::now()->format('Y-m-d'),
            $request->section_id
        );

        $filename = 'attendance_trends_' . Carbon::now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Start Date', $data['start_date'] ?? '']);
            fputcsv($out, ['End Date', $data['end_date'] ?? '']);
            fputcsv($out, []);

            fputcsv($out, ['Date', 'Total', 'Present', 'Late', 'Absent', 'Attendance Rate']);
            foreach (($data['trends'] ?? collect()) as $row) {
                fputcsv($out, [
                    $row['date'] ?? '',
                    $row['total'] ?? 0,
                    $row['present'] ?? 0,
                    $row['late'] ?? 0,
                    $row['absent'] ?? 0,
                    ($row['attendance_rate'] ?? 0) . '%',
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Export student report to CSV
     */
    public function exportStudentCSV(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
        ]);

        $filename = $this->reportService->exportStudentAttendanceCSV($request->student_id);

        return response()->download($filename)->deleteFileAfterSend();
    }
}
