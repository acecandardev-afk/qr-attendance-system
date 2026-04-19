<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Concerns\ManagesEnrollmentSchedules;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\User;
use App\Support\NameConcatSql;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleBulkEnrollmentController extends Controller
{
    use ManagesEnrollmentSchedules;

    /**
     * JSON list of students for the enrollment picker (all active students in the platform).
     */
    public function candidates(Request $request, Schedule $schedule)
    {
        $faculty = Auth::user();
        abort_unless((int) $schedule->faculty_id === (int) $faculty->id, 403);

        $query = User::query()
            ->students()
            ->active()
            ->whereNull('deleted_at');

        if ($request->filled('q_name')) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $request->q_name)).'%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhereRaw(NameConcatSql::firstSpaceLastTrimmed().' like ?', [$term]);
            });
        }

        if ($request->filled('q_user_id')) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $request->q_user_id)).'%';
            $query->where('user_id', 'like', $term);
        }

        $students = $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(400)
            ->get(['id', 'user_id', 'first_name', 'middle_name', 'last_name']);

        return response()->json(['students' => $students]);
    }

    /**
     * Enroll one or more students into this class (schedule).
     */
    public function store(Request $request, Schedule $schedule)
    {
        $faculty = Auth::user();
        abort_unless((int) $schedule->faculty_id === (int) $faculty->id, 403);

        $schedule->loadMissing(['section', 'course']);

        if (! $schedule->section) {
            return redirect()
                ->route('faculty.sessions.index')
                ->with('error', 'This class is missing section information.');
        }

        $validated = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $added = 0;
        $notes = [];

        foreach (array_unique(array_map('intval', $validated['student_ids'])) as $studentId) {
            $student = User::query()->find($studentId);
            if (! $student || ! $student->isStudent()) {
                $notes[] = 'Skipped a selection that is not an active student.';

                continue;
            }

            $result = $this->enrollStudentForSchedule($schedule, $studentId);

            if ($result['counted']) {
                $added++;
            }
            if ($result['note'] !== null) {
                $notes[] = $result['note'];
            }
        }

        $msg = $added === 0
            ? 'No students were added. See notes below if anything was skipped.'
            : $added.' student'.($added === 1 ? '' : 's').' added to this class.';

        return redirect()
            ->route('faculty.sessions.index')
            ->with($added > 0 ? 'success' : 'status', $msg)
            ->with('enroll_flash_notes', array_slice(array_values(array_unique($notes)), 0, 30));
    }

    /**
     * @return array{counted: bool, note: ?string}
     */
    private function enrollStudentForSchedule(Schedule $schedule, int $studentId): array
    {
        $section = $schedule->section;
        if (! $section) {
            return ['counted' => false, 'note' => 'Missing section for this class.'];
        }

        $schoolYear = (string) $section->school_year;
        $semester = (string) $section->semester;

        $existing = Enrollment::query()
            ->where('student_id', $studentId)
            ->where('section_id', $section->id)
            ->where('school_year', $schoolYear)
            ->where('semester', $semester)
            ->whereIn('status', [Enrollment::STATUS_PENDING, Enrollment::STATUS_ENROLLED])
            ->with('schedules')
            ->first();

        if ($existing) {
            if ($existing->schedules->isEmpty()) {
                return [
                    'counted' => false,
                    'note' => $studentId.': already in this section for all classes.',
                ];
            }

            if ($existing->schedules->contains('id', $schedule->id)) {
                return [
                    'counted' => false,
                    'note' => null,
                ];
            }

            $merged = $existing->schedules->pluck('id')->push($schedule->id)->unique()->values()->all();

            if ($this->enrollmentDuplicateExists(
                $studentId,
                (int) $section->id,
                $schoolYear,
                $semester,
                $merged,
                (int) $existing->id,
            )) {
                return ['counted' => false, 'note' => $studentId.': could not merge schedules (duplicate conflict).'];
            }

            $existing->schedules()->sync($merged);
            if ($existing->isPending()) {
                $existing->update(['status' => Enrollment::STATUS_ENROLLED]);
            }

            return ['counted' => true, 'note' => null];
        }

        if ($this->enrollmentDuplicateExists(
            $studentId,
            (int) $section->id,
            $schoolYear,
            $semester,
            [(int) $schedule->id],
        )) {
            return ['counted' => false, 'note' => 'Student #'.$studentId.': already has this class enrollment for the term.'];
        }

        $enrollment = Enrollment::create([
            'student_id' => $studentId,
            'section_id' => $section->id,
            'school_year' => $schoolYear,
            'semester' => $semester,
            'status' => Enrollment::STATUS_ENROLLED,
        ]);
        $enrollment->schedules()->sync([(int) $schedule->id]);

        return ['counted' => true, 'note' => null];
    }
}
