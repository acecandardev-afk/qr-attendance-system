<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Section;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        ]);

        $dailyStats = $this->reportService->getDailyAttendanceStats($request->date);

        return view('admin.reports.index', compact('dailyStats'));
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
