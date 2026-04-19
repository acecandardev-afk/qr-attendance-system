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
use Illuminate\Validation\Rule;

class EnrollmentController extends Controller
{
    use ManagesEnrollmentSchedules;

    /**
     * @param  array<int, int>  $sectionIds
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    protected function studentsSelectableByFaculty(User $faculty, array $sectionIds)
    {
        $rosterIds = Enrollment::query()
            ->whereIn('section_id', $sectionIds)
            ->where('status', 'enrolled')
            ->pluck('student_id')
            ->unique()
            ->filter()
            ->all();

        $base = User::students()->active();

        if ($faculty->department_id && $rosterIds !== []) {
            $students = $base->where(function ($q) use ($faculty, $rosterIds) {
                $q->where('department_id', $faculty->department_id)
                    ->orWhereIn('id', $rosterIds);
            });
        } elseif ($faculty->department_id) {
            $students = $base->where('department_id', $faculty->department_id);
        } elseif ($rosterIds !== []) {
            $students = $base->whereIn('id', $rosterIds);
        } else {
            $students = $base->whereRaw('1 = 0');
        }

        return $students->orderBy('last_name')->orderBy('first_name')->get();
    }

    protected function facultySectionIds(User $faculty): array
    {
        return Schedule::query()
            ->where('faculty_id', $faculty->id)
            ->where('status', 'active')
            ->distinct()
            ->pluck('section_id')
            ->all();
    }

    protected function facultyOwnsEnrollmentRequest(Enrollment $enrollment, User $faculty): bool
    {
        return $enrollment->schedules()->where('faculty_id', $faculty->id)->exists();
    }

    public function index()
    {
        $faculty = Auth::user();
        $sectionIds = $this->facultySectionIds($faculty);

        if ($sectionIds === []) {
            return view('faculty.enrollments.index', [
                'pending' => collect(),
                'mySchedules' => collect(),
                'noTeachingSections' => true,
            ]);
        }

        $pending = Enrollment::with([
            'student' => fn ($q) => $q->withTrashed(),
            'section' => fn ($q) => $q->withTrashed(),
            'schedules.course',
        ])
            ->pending()
            ->whereIn('section_id', $sectionIds)
            ->whereHas('schedules', fn ($q) => $q->where('faculty_id', $faculty->id))
            ->latest()
            ->get();

        $mySchedules = Schedule::query()
            ->where('faculty_id', $faculty->id)
            ->where('status', 'active')
            ->with(['course', 'section'])
            ->orderByDayPattern()
            ->orderBy('start_time')
            ->get();

        $noTeachingSections = false;

        return view('faculty.enrollments.index', compact('pending', 'mySchedules', 'noTeachingSections'));
    }

    public function approve(string $enrollment)
    {
        $faculty = Auth::user();
        $enrollment = Enrollment::with('schedules')->findOrFail($enrollment);

        if (! $enrollment->isPending()) {
            return back()->with('error', 'This request is no longer waiting for you.');
        }

        if (! $this->facultyOwnsEnrollmentRequest($enrollment, $faculty)) {
            abort(403);
        }

        $enrollment->update(['status' => Enrollment::STATUS_ENROLLED]);

        return back()->with('success', 'The student has been added to your class.');
    }

    public function decline(string $enrollment)
    {
        $faculty = Auth::user();
        $enrollment = Enrollment::with('schedules')->findOrFail($enrollment);

        if (! $enrollment->isPending()) {
            return back()->with('error', 'This request is no longer waiting for you.');
        }

        if (! $this->facultyOwnsEnrollmentRequest($enrollment, $faculty)) {
            abort(403);
        }

        $enrollment->delete();

        return back()->with('success', 'You chose not to add this student. They can send another request if they need to.');
    }

    public function edit(string $enrollment)
    {
        $faculty = Auth::user();
        $sectionIds = $this->facultySectionIds($faculty);

        $enrollment = Enrollment::withTrashed()->with(['schedules.course'])->findOrFail($enrollment);

        if ($enrollment->isPending()) {
            return redirect()->route('faculty.enrollments.index')
                ->with('error', 'Use Approve or Not now on the main page for requests that are still waiting.');
        }

        if (! in_array((int) $enrollment->section_id, $sectionIds, true)) {
            abort(403, 'You can only edit enrollments for sections where you teach.');
        }

        $sections = Section::active()->whereIn('id', $sectionIds)->orderBy('name')->get();
        $students = $this->studentsSelectableByFaculty($faculty, $sectionIds);
        $schedulesBySection = $this->schedulesGroupedForFacultySections($faculty, $sections);

        return view('faculty.enrollments.edit', compact('enrollment', 'students', 'sections', 'schedulesBySection'));
    }

    public function update(Request $request, string $enrollment)
    {
        $faculty = Auth::user();
        $sectionIds = $this->facultySectionIds($faculty);

        $enrollment = Enrollment::withTrashed()->findOrFail($enrollment);

        if ($enrollment->isPending()) {
            return redirect()->route('faculty.enrollments.index')
                ->with('error', 'You cannot change a request that is still waiting. Approve or decline it first.');
        }

        if (! in_array((int) $enrollment->section_id, $sectionIds, true)) {
            abort(403, 'You can only edit enrollments for sections where you teach.');
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'section_id' => 'required|exists:sections,id',
            'school_year' => 'required|string|max:255',
            'semester' => ['required', Rule::in(Enrollment::SEMESTERS)],
            'status' => 'required|in:enrolled,dropped,completed',
            'schedule_ids' => 'nullable|array',
            'schedule_ids.*' => 'integer|exists:schedules,id',
        ]);

        if (! in_array((int) $validated['section_id'], $sectionIds, true)) {
            return back()->withInput()->with('error', 'You can only assign students to sections where you teach.');
        }

        $allowedStudentIds = $this->studentsSelectableByFaculty($faculty, $sectionIds)->pluck('id')->all();
        if (! in_array((int) $validated['student_id'], $allowedStudentIds, true)) {
            return back()->withInput()->with('error', 'You can only choose students from your department or who are already in your classes.');
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
            ->with('success', 'The student was removed from this class list.');
    }
}
