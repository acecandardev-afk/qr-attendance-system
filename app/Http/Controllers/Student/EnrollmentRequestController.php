<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Concerns\ManagesEnrollmentSchedules;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrollmentRequestController extends Controller
{
    use ManagesEnrollmentSchedules;

    public function store(Request $request)
    {
        $student = Auth::user();
        abort_unless($student->isStudent(), 403);

        $data = $request->validate([
            'schedule_id' => ['required', 'integer', 'exists:schedules,id'],
        ]);

        $schedule = Schedule::query()
            ->with(['section', 'course', 'faculty'])
            ->where('id', $data['schedule_id'])
            ->where('status', 'active')
            ->first();

        if (! $schedule || ! $schedule->section || ! $schedule->course || ! $schedule->faculty) {
            return back()->with('error', 'That class is not open for joining right now.');
        }

        $section = $schedule->section;

        if ($this->enrollmentDuplicateExists(
            (int) $student->id,
            (int) $section->id,
            (string) $section->school_year,
            (string) $section->semester,
            [(int) $schedule->id],
        )) {
            return back()->with('error', 'You already joined or requested this class.');
        }

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'school_year' => $section->school_year,
            'semester' => $section->semester,
            'status' => Enrollment::STATUS_PENDING,
        ]);

        $enrollment->schedules()->sync([(int) $schedule->id]);

        return back()->with('success', 'Your request was sent. Your instructor will let you know when you are added to the class.');
    }
}
