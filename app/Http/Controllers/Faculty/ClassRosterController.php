<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;

class ClassRosterController extends Controller
{
    public function show(Schedule $schedule)
    {
        $faculty = Auth::user();
        abort_unless($faculty->isFaculty(), 403);
        abort_unless((int) $schedule->faculty_id === (int) $faculty->id, 403);
        abort_unless($schedule->status === 'active', 404);

        $schedule->load(['course', 'section']);

        $enrollments = Enrollment::query()
            ->enrolled()
            ->where('section_id', $schedule->section_id)
            ->where(function ($q) use ($schedule) {
                $q->whereDoesntHave('schedules')
                    ->orWhereHas('schedules', fn ($q) => $q->where('schedules.id', $schedule->id));
            })
            ->with(['student' => fn ($q) => $q->withTrashed()])
            ->get()
            ->sortBy(function ($e) {
                $s = $e->student;

                return strtolower(($s->last_name ?? 'z').' '.($s->first_name ?? ''));
            })
            ->values();

        return view('faculty.classes.roster', [
            'schedule' => $schedule,
            'enrollments' => $enrollments,
        ]);
    }
}
