<?php

namespace App\Http\Controllers\Concerns;

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
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('section_id')
            ->map(fn ($rows) => $rows->map(fn (Schedule $s) => [
                'id' => $s->id,
                'label' => ($s->course?->code ?? 'Course').' — '.$s->day_of_week.' '.$s->time_range.' — '.($s->room ?? '—').($s->status !== 'active' ? ' [Inactive]' : ''),
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
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('section_id')
            ->map(fn ($rows) => $rows->map(fn (Schedule $s) => [
                'id' => $s->id,
                'label' => ($s->course?->code ?? 'Course').' — '.$s->day_of_week.' '.$s->time_range.' — '.($s->room ?? '—').($s->status !== 'active' ? ' [Inactive]' : ''),
            ])->values());
    }

    /**
     * @param  array<int, mixed>|null  $scheduleIds
     * @return array<int, int>|null  null if invalid (not all schedules belong to section)
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
}
