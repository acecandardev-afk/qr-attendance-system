<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RedirectsMissingAdminRecord;
use App\Http\Controllers\Concerns\ValidatesBulkIds;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    use RedirectsMissingAdminRecord;
    use ValidatesBulkIds;

    public function index(Request $request)
    {
        $query = Schedule::with(['course', 'section', 'faculty']);

        if ($request->filled('faculty_id')) {
            $query->where('faculty_id', $request->faculty_id);
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        $schedules = $query->latest()->paginate(20);
        $faculty = User::faculty()->active()->get();
        $sections = Section::active()->get();

        return view('admin.schedules.index', compact('schedules', 'faculty', 'sections'));
    }

    public function show($schedule)
    {
        return $this->redirectShowToEditOrIndex(
            Schedule::class,
            $schedule,
            'admin.schedules.index',
            'admin.schedules.edit',
            'That schedule no longer exists or was removed.',
        );
    }

    public function create()
    {
        $courses = Course::active()->get();
        $sections = Section::active()->get();
        $faculty = User::faculty()->active()->get();

        return view('admin.schedules.create', compact('courses', 'sections', 'faculty'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'section_id' => 'required|exists:sections,id',
            'faculty_id' => 'required|exists:users,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:255',
            'network_identifier' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        Schedule::create($validated);

        return redirect()->route('admin.schedules.index')
            ->with('success', 'Schedule created successfully!');
    }

    public function edit(Schedule $schedule)
    {
        $courses = Course::active()->get();
        $sections = Section::active()->get();
        $faculty = User::faculty()->active()->get();

        return view('admin.schedules.edit', compact('schedule', 'courses', 'sections', 'faculty'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'section_id' => 'required|exists:sections,id',
            'faculty_id' => 'required|exists:users,id',
            'day_of_week' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:255',
            'network_identifier' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $schedule->update($validated);

        return redirect()->route('admin.schedules.index')
            ->with('success', 'Schedule updated successfully!');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();

        return redirect()->route('admin.schedules.index')
            ->with('success', 'Schedule deleted successfully!');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $this->validatedBulkIds($request, 'schedules');
        Schedule::whereIn('id', $ids)->get()->each->delete();

        return back()->with('success', count($ids).' schedule(s) removed.');
    }
}
