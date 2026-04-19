<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Faculty's own attendance report
     */
    public function index(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $faculty = Auth::user();

        $data = $this->reportService->getFacultyAttendanceReport(
            $faculty->id,
            $request->start_date,
            $request->end_date
        );

        return view('faculty.reports.index', compact('data'));
    }

    public function exportMyReportCSV(Request $request): StreamedResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $faculty = Auth::user();

        $data = $this->reportService->getFacultyAttendanceReport(
            $faculty->id,
            $request->start_date,
            $request->end_date
        );

        $facultyName = $data['faculty']->full_name_without_middle ?? $data['faculty']->full_name ?? 'faculty';
        $filename = 'my_attendance_report_'.preg_replace('/[^A-Za-z0-9\-_]+/', '_', $facultyName).'_'.now()->format('Ymd_His').'.csv';

        $detailRows = $this->reportService->getFacultyAttendanceDetailRows(
            $faculty->id,
            $request->start_date,
            $request->end_date
        );

        return response()->streamDownload(function () use ($data, $request, $detailRows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Faculty', $data['faculty']->full_name ?? '']);
            fputcsv($out, ['Start Date', (string) ($request->start_date ?? '')]);
            fputcsv($out, ['End Date', (string) ($request->end_date ?? '')]);
            fputcsv($out, []);
            fputcsv($out, ['Total Sessions', $data['total_sessions'] ?? 0]);
            fputcsv($out, ['Active Sessions', $data['active_sessions'] ?? 0]);
            fputcsv($out, ['Closed Sessions', $data['closed_sessions'] ?? 0]);
            fputcsv($out, ['Expired Sessions', $data['expired_sessions'] ?? 0]);
            fputcsv($out, ['Total Attendance Records', $data['total_attendance_records'] ?? 0]);
            fputcsv($out, ['Present (records)', $data['total_present'] ?? 0]);
            fputcsv($out, ['Late (records)', $data['total_late'] ?? 0]);
            fputcsv($out, ['Absent (records)', $data['total_absent'] ?? 0]);
            fputcsv($out, []);

            fputcsv($out, ['Course Code', 'Course', 'Total Sessions', 'Total Attendance', 'Avg per Session', 'Present', 'Late', 'Absent']);
            foreach (($data['by_course'] ?? collect()) as $courseData) {
                $course = $courseData['course'] ?? null;
                fputcsv($out, [
                    $course->code ?? '',
                    $course->name ?? '',
                    $courseData['total_sessions'] ?? 0,
                    $courseData['total_attendance_records'] ?? 0,
                    $courseData['average_attendance_per_session'] ?? 0,
                    $courseData['present'] ?? 0,
                    $courseData['late'] ?? 0,
                    $courseData['absent'] ?? 0,
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Session date', 'Session time', 'Course code', 'Course name', 'Section', 'Student ID', 'Student name', 'Status']);
            foreach ($detailRows as $row) {
                fputcsv($out, [
                    $row['session_date'],
                    $row['session_time'],
                    $row['course_code'],
                    $row['course_name'],
                    $row['section_name'],
                    $row['student_user_id'],
                    $row['student_name'],
                    $row['status'],
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function classReport(Request $request)
    {
        $request->validate([
            'section_id' => 'nullable|integer',
            'course_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $faculty = Auth::user();
        $sections = $faculty->facultySchedules()->with('section')->get()->pluck('section')->unique('id');

        $data = null;

        if ($request->filled('section_id')) {
            // Verify faculty has access to this section
            $hasAccess = $faculty->facultySchedules()
                ->where('section_id', $request->section_id)
                ->exists();

            if ($hasAccess) {
                $data = $this->reportService->getSectionAttendanceReport(
                    $request->section_id,
                    $request->course_id,
                    $request->start_date,
                    $request->end_date
                );
            }
        }

        return view('faculty.reports.class', compact('sections', 'data'));
    }

    public function exportClassReportCSV(Request $request): StreamedResponse
    {
        $request->validate([
            'section_id' => 'required|integer',
            'course_id' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $faculty = Auth::user();

        $hasAccess = $faculty->facultySchedules()
            ->where('section_id', $request->section_id)
            ->exists();

        abort_unless($hasAccess, 403);

        $data = $this->reportService->getSectionAttendanceReport(
            (int) $request->section_id,
            $request->course_id ? (int) $request->course_id : null,
            $request->start_date,
            $request->end_date
        );

        $sectionName = $data['section']->name ?? 'section';
        $filename = 'class_attendance_'.preg_replace('/[^A-Za-z0-9\-_]+/', '_', $sectionName).'_'.now()->format('Ymd_His').'.csv';

        $detailRows = $this->reportService->getSectionAttendanceDetailRows(
            (int) $request->section_id,
            $request->course_id ? (int) $request->course_id : null,
            $request->start_date,
            $request->end_date
        );

        return response()->streamDownload(function () use ($data, $request, $detailRows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Section', $data['section']->name ?? '']);
            fputcsv($out, ['Start Date', (string) ($request->start_date ?? '')]);
            fputcsv($out, ['End Date', (string) ($request->end_date ?? '')]);
            fputcsv($out, []);
            fputcsv($out, ['Total Sessions', $data['total_sessions'] ?? 0]);
            fputcsv($out, ['Total Students', $data['total_students'] ?? 0]);
            fputcsv($out, ['Average Attendance Rate', round($data['overall_stats']['average_attendance_rate'] ?? 0, 2).'%']);
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
                    ($row['attendance_rate'] ?? 0).'%',
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Session date', 'Session time', 'Course code', 'Course name', 'Section', 'Student ID', 'Student name', 'Status']);
            foreach ($detailRows as $row) {
                fputcsv($out, [
                    $row['session_date'],
                    $row['session_time'],
                    $row['course_code'],
                    $row['course_name'],
                    $row['section_name'],
                    $row['student_user_id'],
                    $row['student_name'],
                    $row['status'],
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
