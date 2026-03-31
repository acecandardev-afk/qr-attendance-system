<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Concerns\ManagesEnrollmentSchedules;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    use ManagesEnrollmentSchedules;

    /**
     * Section IDs where this faculty has at least one active schedule.
     *
     * @return array<int, int>
     */
    protected function facultySectionIds(User $faculty): array
    {
        return Schedule::query()
            ->where('faculty_id', $faculty->id)
            ->where('status', 'active')
            ->distinct()
            ->pluck('section_id')
            ->all();
    }

    public function index(Request $request)
    {
        $faculty = Auth::user();
        $sectionIds = $this->facultySectionIds($faculty);

        if ($sectionIds === []) {
            return view('faculty.enrollments.index', [
                'enrollments' => Enrollment::query()->whereRaw('1 = 0')->paginate(20),
                'sections' => collect(),
                'noTeachingSections' => true,
            ]);
        }

        $query = Enrollment::with([
            'student' => fn ($q) => $q->withTrashed(),
            'section' => fn ($q) => $q->withTrashed(),
            'schedules.course',
        ])->whereIn('section_id', $sectionIds);

        if ($request->filled('section_id')) {
            if (in_array((int) $request->section_id, $sectionIds, true)) {
                $query->where('section_id', $request->section_id);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $enrollments = $query->latest()->paginate(20);
        $sections = Section::active()->whereIn('id', $sectionIds)->orderBy('name')->get();

        return view('faculty.enrollments.index', compact('enrollments', 'sections'));
    }

    public function create()
    {
        $faculty = Auth::user();
        $sectionIds = $this->facultySectionIds($faculty);

        if ($sectionIds === []) {
            return redirect()->route('faculty.enrollments.index')
                ->with('error', 'You have no class schedules yet. Ask an administrator to assign you to schedules first.');
        }

        $sections = Section::active()->whereIn('id', $sectionIds)->orderBy('name')->get();
        $students = User::students()->active()->orderBy('last_name')->orderBy('first_name')->get();
        $schedulesBySection = $this->schedulesGroupedForFacultySections($faculty, $sections);

        return view('faculty.enrollments.create', compact('students', 'sections', 'schedulesBySection'));
    }

    public function store(Request $request)
    {
        $faculty = Auth::user();
        $sectionIds = $this->facultySectionIds($faculty);

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'section_id' => 'required|exists:sections,id',
            'school_year' => 'required|string|max:255',
            'semester' => 'required|string|max:255',
            'status' => 'required|in:enrolled,dropped,completed',
            'schedule_ids' => 'nullable|array',
            'schedule_ids.*' => 'integer|exists:schedules,id',
        ]);

        if (! in_array((int) $validated['section_id'], $sectionIds, true)) {
            return back()->withInput()->with('error', 'You can only enroll students in sections where you teach.');
        }

        $myScheduleIds = $this->validatedScheduleIdsForFacultySection(
            $faculty,
            (int) $validated['section_id'],
            $request->input('schedule_ids', [])
        );
        if ($myScheduleIds === null) {
            return back()->withInput()->with('error', 'Choose only your own class schedules for this section.');
        }

        if ($this->enrollmentDuplicateExists(
            (int) $validated['student_id'],
            (int) $validated['section_id'],
            $validated['school_year'],
            $validated['semester'],
            $myScheduleIds,
        )) {
            return back()->withInput()->with(
                'error',
                'This student already has an enrollment for that section and term with the same class schedule(s). Add another enrollment with a different day/time or subject, or edit the existing one.'
            );
        }

        $enrollment = Enrollment::create($validated);

        if ($myScheduleIds !== []) {
            $enrollment->schedules()->sync($myScheduleIds);
        }

        return redirect()->route('faculty.enrollments.index')
            ->with('success', 'Enrollment saved. Each student can have different class schedules.');
    }

    public function edit(string $enrollment)
    {
        $faculty = Auth::user();
        $sectionIds = $this->facultySectionIds($faculty);

        $enrollment = Enrollment::withTrashed()->with(['schedules.course'])->findOrFail($enrollment);

        if (! in_array((int) $enrollment->section_id, $sectionIds, true)) {
            abort(403, 'You can only edit enrollments for sections where you teach.');
        }

        $sections = Section::active()->whereIn('id', $sectionIds)->orderBy('name')->get();
        $students = User::students()->active()->orderBy('last_name')->orderBy('first_name')->get();
        $schedulesBySection = $this->schedulesGroupedForFacultySections($faculty, $sections);

        return view('faculty.enrollments.edit', compact('enrollment', 'students', 'sections', 'schedulesBySection'));
    }

    public function update(Request $request, string $enrollment)
    {
        $faculty = Auth::user();
        $sectionIds = $this->facultySectionIds($faculty);

        $enrollment = Enrollment::withTrashed()->findOrFail($enrollment);

        if (! in_array((int) $enrollment->section_id, $sectionIds, true)) {
            abort(403, 'You can only edit enrollments for sections where you teach.');
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'section_id' => 'required|exists:sections,id',
            'school_year' => 'required|string|max:255',
            'semester' => 'required|string|max:255',
            'status' => 'required|in:enrolled,dropped,completed',
            'schedule_ids' => 'nullable|array',
            'schedule_ids.*' => 'integer|exists:schedules,id',
        ]);

        if (! in_array((int) $validated['section_id'], $sectionIds, true)) {
            return back()->withInput()->with('error', 'You can only assign students to sections where you teach.');
        }

        $myScheduleIds = $this->validatedScheduleIdsForFacultySection(
            $faculty,
            (int) $validated['section_id'],
            $request->input('schedule_ids', [])
        );
        if ($myScheduleIds === null) {
            return back()->withInput()->with('error', 'Choose only your own class schedules for this section.');
        }

        $otherIds = $enrollment->schedules()
            ->where('faculty_id', '!=', $faculty->id)
            ->pluck('id');

        $merged = $otherIds
            ->merge($myScheduleIds)
            ->unique()
            ->values()
            ->all();

        if ($this->enrollmentDuplicateExists(
            (int) $validated['student_id'],
            (int) $validated['section_id'],
            $validated['school_year'],
            $validated['semester'],
            $merged,
            (int) $enrollment->id,
        )) {
            return back()->withInput()->with(
                'error',
                'This would duplicate another enrollment for the same section, term, and class schedule(s). Choose different schedules or edit the other enrollment.'
            );
        }

        $enrollment->update($validated);

        $enrollment->schedules()->sync($merged);

        return redirect()->route('faculty.enrollments.index')
            ->with('success', 'Enrollment updated.');
    }

    public function destroy(string $enrollment)
    {
        $faculty = Auth::user();
        $sectionIds = $this->facultySectionIds($faculty);

        $enrollment = Enrollment::withTrashed()->findOrFail($enrollment);

        if (! in_array((int) $enrollment->section_id, $sectionIds, true)) {
            abort(403, 'You can only remove enrollments for sections where you teach.');
        }

        if ($enrollment->trashed()) {
            return redirect()->route('faculty.enrollments.index')
                ->with('error', 'This enrollment has already been removed.');
        }

        $enrollment->delete();

        return redirect()->route('faculty.enrollments.index')
            ->with('success', 'Enrollment removed.');
    }
}
