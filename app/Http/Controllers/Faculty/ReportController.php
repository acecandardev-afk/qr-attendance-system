<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    /**
     * Class attendance report for faculty's sections
     */
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
}