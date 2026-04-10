<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ManagesEnrollmentSchedules;
use App\Http\Controllers\Concerns\RedirectsMissingAdminRecord;
use App\Http\Controllers\Concerns\ValidatesBulkIds;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    use ManagesEnrollmentSchedules;
    use RedirectsMissingAdminRecord;
    use ValidatesBulkIds;

    public function index(Request $request)
    {
        $query = Enrollment::with([
            'student' => fn ($q) => $q->withTrashed(),
            'section' => fn ($q) => $q->withTrashed(),
            'schedules.course',
        ]);

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($request->q)).'%';
            $query->whereHas('student', function ($q) use ($term) {
                $q->where('user_id', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhereRaw("trim(concat(coalesce(first_name,''),' ',coalesce(last_name,''))) like ?", [$term]);
            });
        }

        $enrollments = $query->latest()->paginate(20)->withQueryString();
        $sections = Section::active()->get();

        return view('admin.enrollments.index', compact('enrollments', 'sections'));
    }

    public function show($enrollment)
    {
        return $this->redirectShowToEditOrIndex(
            Enrollment::class,
            $enrollment,
            'admin.enrollments.index',
            'admin.enrollments.edit',
            'That enrollment no longer exists or was removed.',
            true,
        );
    }

    public function create()
    {
        $students = User::students()->active()->get();
        $sections = Section::active()->get();
        $schedulesBySection = $this->schedulesGroupedForSections($sections);

        return view('admin.enrollments.create', compact('students', 'sections', 'schedulesBySection'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'section_id' => 'required|exists:sections,id',
            'school_year' => 'required|string|max:255',
            'semester' => 'required|string|max:255',
            'status' => 'required|in:enrolled,dropped,completed',
            'schedule_ids' => 'nullable|array',
            'schedule_ids.*' => 'integer|exists:schedules,id',
        ]);

        $scheduleIds = $this->validatedScheduleIdsForSection(
            (int) $validated['section_id'],
            $request->input('schedule_ids', [])
        );
        if ($scheduleIds === null) {
            return back()->withInput()->with('error', 'Please choose only schedules that belong to the selected section.');
        }

        if ($this->enrollmentDuplicateExists(
            (int) $validated['student_id'],
            (int) $validated['section_id'],
            $validated['school_year'],
            $validated['semester'],
            $scheduleIds,
        )) {
            return back()->withInput()->with(
                'error',
                'This student already has an enrollment for that section and term with the same class schedule(s). Use a different day/time or subject, or edit the existing enrollment.'
            );
        }

        $enrollment = Enrollment::create($validated);
        if (! empty($scheduleIds)) {
            $enrollment->schedules()->sync($scheduleIds);
        }

        return redirect()->route('admin.enrollments.index')
            ->with('success', 'Enrollment created successfully!');
    }

    public function edit(string $enrollment)
    {
        $enrollment = Enrollment::withTrashed()->with(['schedules.course'])->findOrFail($enrollment);

        $students = User::students()->active()->get();
        $sections = Section::active()->get();
        $schedulesBySection = $this->schedulesGroupedForSections($sections);

        return view('admin.enrollments.edit', compact('enrollment', 'students', 'sections', 'schedulesBySection'));
    }

    public function update(Request $request, string $enrollment)
    {
        $enrollment = Enrollment::withTrashed()->findOrFail($enrollment);

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'section_id' => 'required|exists:sections,id',
            'school_year' => 'required|string|max:255',
            'semester' => 'required|string|max:255',
            'status' => 'required|in:enrolled,dropped,completed',
            'schedule_ids' => 'nullable|array',
            'schedule_ids.*' => 'integer|exists:schedules,id',
        ]);

        $scheduleIds = $this->validatedScheduleIdsForSection(
            (int) $validated['section_id'],
            $request->input('schedule_ids', [])
        );
        if ($scheduleIds === null) {
            return back()->withInput()->with('error', 'Please choose only schedules that belong to the selected section.');
        }

        if ($this->enrollmentDuplicateExists(
            (int) $validated['student_id'],
            (int) $validated['section_id'],
            $validated['school_year'],
            $validated['semester'],
            $scheduleIds,
            (int) $enrollment->id,
        )) {
            return back()->withInput()->with(
                'error',
                'Another enrollment already uses this section, term, and the same class schedule(s) for this student.'
            );
        }

        $enrollment->update($validated);
        $enrollment->schedules()->sync($scheduleIds);

        return redirect()->route('admin.enrollments.index')
            ->with('success', 'Enrollment updated successfully!');
    }

    public function destroy(string $enrollment)
    {
        $enrollment = Enrollment::withTrashed()->findOrFail($enrollment);

        if ($enrollment->trashed()) {
            return redirect()->route('admin.enrollments.index')
                ->with('error', 'This enrollment has already been removed.');
        }

        $enrollment->delete();

        return redirect()->route('admin.enrollments.index')
            ->with('success', 'Enrollment deleted successfully!');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $this->validatedBulkIds($request, 'enrollments');
        $rows = Enrollment::whereIn('id', $ids)->get();
        $n = $rows->count();
        $rows->each->delete();

        return back()->with('success', $n.' enrollment(s) removed.');
    }
}
