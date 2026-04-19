<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RedirectsMissingAdminRecord;
use App\Http\Controllers\Concerns\ValidatesBulkIds;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\User;
use App\Rules\ValidScheduleEndTime;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    use RedirectsMissingAdminRecord;
    use ValidatesBulkIds;

    public function index(Request $request)
    {
        $query = Schedule::with([
            'course' => fn ($q) => $q->withTrashed(),
            'section' => fn ($q) => $q->withTrashed(),
            'faculty' => fn ($q) => $q->withTrashed(),
        ]);

        if ($request->filled('faculty_id')) {
            $query->where('faculty_id', $request->faculty_id);
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        $schedules = $query->orderByDayPattern()->orderBy('start_time')->paginate(20);
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
            'day_of_week' => ['required', Rule::in(Schedule::DAY_PATTERNS)],
            'start_time' => 'required|date_format:H:i',
            'end_time' => ['required', 'date_format:H:i', new ValidScheduleEndTime($request->input('start_time'))],
            'room' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validated['status'] === 'active' && Schedule::sectionHasScheduleTimeConflict(
            (int) $validated['section_id'],
            $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time'],
        )) {
            throw ValidationException::withMessages([
                'start_time' => 'This section already has an active class that overlaps this schedule or leaves less than one minute between classes.',
            ]);
        }

        Schedule::create($validated);

        return redirect()->route('admin.schedules.index')
            ->with('success', 'Class schedule saved.');
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
            'day_of_week' => ['required', Rule::in(Schedule::DAY_PATTERNS)],
            'start_time' => 'required|date_format:H:i',
            'end_time' => ['required', 'date_format:H:i', new ValidScheduleEndTime($request->input('start_time'))],
            'room' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validated['status'] === 'active' && Schedule::sectionHasScheduleTimeConflict(
            (int) $validated['section_id'],
            $validated['day_of_week'],
            $validated['start_time'],
            $validated['end_time'],
            $schedule->id,
        )) {
            throw ValidationException::withMessages([
                'start_time' => 'This section already has an active class that overlaps this schedule or leaves less than one minute between classes.',
            ]);
        }

        $schedule->update($validated);

        return redirect()->route('admin.schedules.index')
            ->with('success', 'Class schedule updated.');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();

        return redirect()->route('admin.schedules.index')
            ->with('success', 'Schedule archived.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $this->validatedBulkIds($request, 'schedules');
        Schedule::whereIn('id', $ids)->get()->each->delete();

        return back()->with('success', count($ids).' schedule(s) archived.');
    }
}
