<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Collection;

trait ManagesEnrollmentSchedules
{
    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Section>|array  $sections
     * @return Collection<string|int, \Illuminate\Support\Collection<int, array{id:int,label:string}>>
     */
    protected function schedulesGroupedForSections($sections): Collection
    {
        if ($sections->isEmpty()) {
            return collect();
        }

        return Schedule::query()
            ->with('course')
            ->whereIn('section_id', $sections->pluck('id'))
            ->orderByDayPattern()
            ->orderBy('start_time')
            ->get()
            ->groupBy('section_id')
            ->map(fn ($rows) => $rows->map(fn (Schedule $s) => [
                'id' => $s->id,
                'label' => ($s->course?->code ?? 'Subject').' — '.$s->day_of_week.' '.$s->time_range.' — '.($s->room ?? '—').($s->status !== 'active' ? ' [Inactive]' : ''),
            ])->values());
    }

    /**
     * Active schedules for this faculty member, grouped by section (for enrollment pickers).
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Section>|array  $sections
     * @return Collection<string|int, \Illuminate\Support\Collection<int, array{id:int,label:string}>>
     */
    protected function schedulesGroupedForFacultySections(User $faculty, $sections): Collection
    {
        if ($sections->isEmpty()) {
            return collect();
        }

        return Schedule::query()
            ->with('course')
            ->where('faculty_id', $faculty->id)
            ->whereIn('section_id', $sections->pluck('id'))
            ->orderByDayPattern()
            ->orderBy('start_time')
            ->get()
            ->groupBy('section_id')
            ->map(fn ($rows) => $rows->map(fn (Schedule $s) => [
                'id' => $s->id,
                'label' => ($s->course?->code ?? 'Subject').' — '.$s->day_of_week.' '.$s->time_range.' — '.($s->room ?? '—').($s->status !== 'active' ? ' [Inactive]' : ''),
            ])->values());
    }

    /**
     * @param  array<int, mixed>|null  $scheduleIds
     * @return array<int, int>|null null if invalid (not all schedules belong to section)
     */
    protected function validatedScheduleIdsForSection(int $sectionId, $scheduleIds): ?array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $scheduleIds))));
        if ($ids === []) {
            return [];
        }

        $valid = Schedule::query()
            ->where('section_id', $sectionId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        if (count($valid) !== count($ids)) {
            return null;
        }

        return $ids;
    }

    /**
     * Schedules must belong to the section and be assigned to this faculty member.
     *
     * @param  array<int, mixed>|null  $scheduleIds
     * @return array<int, int>|null
     */
    protected function validatedScheduleIdsForFacultySection(User $faculty, int $sectionId, $scheduleIds): ?array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $scheduleIds))));
        if ($ids === []) {
            return [];
        }

        $valid = Schedule::query()
            ->where('faculty_id', $faculty->id)
            ->where('section_id', $sectionId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        if (count($valid) !== count($ids)) {
            return null;
        }

        return $ids;
    }

    /**
     * Normalized key for comparing schedule selections. Empty string = no schedules (counts for whole section).
     *
     * @param  array<int, int|string>  $scheduleIds
     */
    protected function enrollmentScheduleSignature(array $scheduleIds): string
    {
        $ids = array_values(array_unique(array_map('intval', $scheduleIds)));
        sort($ids);

        return implode(',', $ids);
    }

    /**
     * Whether another enrollment already uses the same student, section, term, and schedule set.
     *
     * @param  array<int, int>  $scheduleIds  Final schedule IDs that will be stored on the enrollment
     */
    protected function enrollmentDuplicateExists(
        int $studentId,
        int $sectionId,
        string $schoolYear,
        string $semester,
        array $scheduleIds,
        ?int $exceptEnrollmentId = null,
    ): bool {
        $signature = $this->enrollmentScheduleSignature($scheduleIds);

        $query = Enrollment::query()
            ->where('student_id', $studentId)
            ->where('section_id', $sectionId)
            ->where('school_year', $schoolYear)
            ->where('semester', $semester);

        if ($exceptEnrollmentId !== null) {
            $query->where('id', '!=', $exceptEnrollmentId);
        }

        foreach ($query->with('schedules:id')->get() as $enrollment) {
            $existing = $this->enrollmentScheduleSignature($enrollment->schedules->pluck('id')->all());
            if ($existing === $signature) {
                return true;
            }
        }

        return false;
    }
}
