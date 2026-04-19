<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\Schedule;
use App\Models\Section;
use App\Rules\ValidScheduleEndTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FacultySubjectController extends Controller
{
    /**
     * Create a new subject (course) and the faculty's class schedule for it.
     */
    public function store(Request $request)
    {
        $faculty = Auth::user();
        abort_unless($faculty->isFaculty(), 403);

        $departmentId = $faculty->department_id;

        $rules = [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('courses', 'code')->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'units' => ['required', 'integer', 'min:0'],
            'section_name' => ['required', 'string', 'max:255'],
            'day_of_week' => ['required', Rule::in(Schedule::DAY_PATTERNS)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', new ValidScheduleEndTime($request->input('start_time'))],
        ];

        if (! $departmentId) {
            $rules['department_id'] = ['required', 'exists:departments,id'];
        }

        $validated = $request->validate($rules);

        if (! $departmentId) {
            $departmentId = (int) $validated['department_id'];
        }

        $sectionName = trim(preg_replace('/\s+/u', ' ', $validated['section_name']));

        try {
            DB::transaction(function () use ($validated, $departmentId, $faculty, $sectionName) {
                $section = $this->resolveOrCreateSectionForFaculty($sectionName, $departmentId);

                if (Schedule::sectionHasScheduleTimeConflict(
                    (int) $section->id,
                    $validated['day_of_week'],
                    $validated['start_time'],
                    $validated['end_time'],
                )) {
                    throw ValidationException::withMessages([
                        'start_time' => 'This section already has a class that overlaps this schedule or leaves less than one minute between classes. Use different times or another section.',
                    ]);
                }

                $course = Course::create([
                    'code' => $validated['code'],
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'units' => (int) $validated['units'],
                    'department_id' => $departmentId,
                    'status' => 'active',
                ]);

                Schedule::create([
                    'course_id' => $course->id,
                    'section_id' => (int) $section->id,
                    'faculty_id' => $faculty->id,
                    'day_of_week' => $validated['day_of_week'],
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'status' => 'active',
                ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            $msg = $e->getMessage();
            if (
                str_contains($msg, 'sections_name_school_year_semester_unique')
                || (str_contains($msg, 'UNIQUE constraint failed') && str_contains($msg, 'sections'))
            ) {
                return back()
                    ->withInput()
                    ->with('error', 'Could not save: that section name already exists for this school year and semester under your department. Use a different name or ask an administrator.');
            }
            if (
                str_contains($msg, 'courses.code')
                || (str_contains($msg, 'UNIQUE constraint failed') && str_contains($msg, 'courses'))
            ) {
                return back()
                    ->withInput()
                    ->with('error', 'Could not save: that subject code is already in use. Choose a different code.');
            }
            throw $e;
        }

        return redirect()
            ->route('faculty.sessions.index')
            ->with('success', 'Subject created and added to your schedule.');
    }

    public function edit(Schedule $schedule)
    {
        $faculty = Auth::user();
        abort_unless($faculty->isFaculty(), 403);
        $this->authorizeFacultySchedule($schedule, $faculty);

        $schedule->load([
            'course' => fn ($q) => $q->withTrashed(),
            'section' => fn ($q) => $q->withTrashed(),
        ]);

        $subjectCreateDepartments = ! $faculty->department_id
            ? Department::query()->orderBy('name')->get()
            : collect();

        return view('faculty.subjects.edit', compact('schedule', 'subjectCreateDepartments'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $faculty = Auth::user();
        abort_unless($faculty->isFaculty(), 403);
        $this->authorizeFacultySchedule($schedule, $faculty);

        $schedule->load([
            'course' => fn ($q) => $q->withTrashed(),
            'section' => fn ($q) => $q->withTrashed(),
        ]);

        $course = $schedule->course;
        abort_if(! $course || $course->trashed(), 404);

        $departmentId = $faculty->department_id;

        $rules = [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('courses', 'code')->whereNull('deleted_at')->ignore($course->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'units' => ['required', 'integer', 'min:0'],
            'section_name' => ['required', 'string', 'max:255'],
            'day_of_week' => ['required', Rule::in(Schedule::DAY_PATTERNS)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', new ValidScheduleEndTime($request->input('start_time'))],
        ];

        if (! $departmentId) {
            $rules['department_id'] = ['required', 'exists:departments,id'];
        }

        $validated = $request->validate($rules);

        if (! $departmentId) {
            $departmentId = (int) $validated['department_id'];
        }

        $sectionName = trim(preg_replace('/\s+/u', ' ', $validated['section_name']));

        try {
            DB::transaction(function () use ($validated, $departmentId, $schedule, $course, $sectionName) {
                $section = $this->resolveOrCreateSectionForFaculty($sectionName, $departmentId);

                if ((int) $section->id !== (int) $schedule->section_id) {
                    throw ValidationException::withMessages([
                        'section_name' => 'You cannot move this class to a different section here. Ask an administrator if you need to change sections.',
                    ]);
                }

                if (Schedule::sectionHasScheduleTimeConflict(
                    (int) $section->id,
                    $validated['day_of_week'],
                    $validated['start_time'],
                    $validated['end_time'],
                    $schedule->id,
                )) {
                    throw ValidationException::withMessages([
                        'start_time' => 'This section already has another class that overlaps this schedule or leaves less than one minute between classes.',
                    ]);
                }

                $course->update([
                    'code' => $validated['code'],
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'units' => (int) $validated['units'],
                    'department_id' => $departmentId,
                ]);

                $schedule->update([
                    'day_of_week' => $validated['day_of_week'],
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'status' => 'active',
                ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            $msg = $e->getMessage();
            if (
                str_contains($msg, 'sections_name_school_year_semester_unique')
                || (str_contains($msg, 'UNIQUE constraint failed') && str_contains($msg, 'sections'))
            ) {
                return back()
                    ->withInput()
                    ->with('error', 'Could not save: that section name already exists for this school year and semester under your department. Use a different name or ask an administrator.');
            }
            if (
                str_contains($msg, 'courses.code')
                || (str_contains($msg, 'UNIQUE constraint failed') && str_contains($msg, 'courses'))
            ) {
                return back()
                    ->withInput()
                    ->with('error', 'Could not save: that subject code is already in use. Choose a different code.');
            }
            throw $e;
        }

        return redirect()
            ->route('faculty.sessions.index')
            ->with('success', 'Subject updated.');
    }

    public function destroy(Schedule $schedule)
    {
        $faculty = Auth::user();
        abort_unless($faculty->isFaculty(), 403);
        $this->authorizeFacultySchedule($schedule, $faculty);

        $schedule->load(['course']);

        if ($schedule->attendanceSessions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists()) {
            return redirect()
                ->route('faculty.sessions.index')
                ->with('error', 'Close the active attendance session for this class before removing it.');
        }

        $course = $schedule->course;

        DB::transaction(function () use ($schedule, $course) {
            $schedule->enrollments()->detach();
            $schedule->delete();

            if ($course && ! Schedule::where('course_id', $course->id)->whereNull('deleted_at')->exists()) {
                $course->delete();
            }
        });

        return redirect()
            ->route('faculty.sessions.index')
            ->with('success', 'Subject and schedule removed.');
    }

    protected function authorizeFacultySchedule(Schedule $schedule, $faculty): void
    {
        abort_unless((int) $schedule->faculty_id === (int) $faculty->id, 403);
    }

    /**
     * @throws ValidationException
     */
    protected function resolveOrCreateSectionForFaculty(string $sectionName, int $departmentId): Section
    {
        $schoolYear = $this->defaultSchoolYear();
        $semester = Section::SEMESTERS[0];

        $section = Section::query()
            ->whereNull('deleted_at')
            ->where('school_year', $schoolYear)
            ->where('semester', $semester)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($sectionName)])
            ->first();

        if ($section) {
            if ((int) $section->department_id !== (int) $departmentId) {
                throw ValidationException::withMessages([
                    'section_name' => 'That section name is already used for this school year and semester under another department. Use a different name or ask an administrator.',
                ]);
            }
            if ($section->status !== 'active') {
                throw ValidationException::withMessages([
                    'section_name' => 'That section exists but is not active. Ask an administrator to restore it or use another section name.',
                ]);
            }

            return $section;
        }

        return Section::create([
            'name' => $sectionName,
            'department_id' => $departmentId,
            'year_level' => '—',
            'semester' => $semester,
            'school_year' => $schoolYear,
            'status' => 'active',
        ]);
    }

    /**
     * Academic year string e.g. 2025-2026 (June–May style: June+ starts current–next).
     */
    private function defaultSchoolYear(): string
    {
        $now = now();
        $y = (int) $now->year;
        $m = (int) $now->month;

        return $m >= 6 ? "{$y}-".($y + 1) : ($y - 1)."-{$y}";
    }
}
