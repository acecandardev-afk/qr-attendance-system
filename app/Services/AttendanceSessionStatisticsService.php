<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\Enrollment;
use App\Support\AttendanceConfig;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceSessionStatisticsService
{
    public function absentAfterMinutes(AttendanceSession $session): int
    {
        $session->loadMissing('faculty');
        $m = $session->faculty?->absent_after_minutes;
        if ($m === null || $m < 0) {
            return max(0, (int) AttendanceConfig::get('absent_after_minutes', 30));
        }

        return (int) $m;
    }

    public function enrolledStudentIds(AttendanceSession $session): Collection
    {
        $session->loadMissing('schedule');
        if (! $session->schedule) {
            return collect();
        }

        return Enrollment::query()
            ->eligibleForSchedule($session->schedule)
            ->pluck('student_id')
            ->unique()
            ->values();
    }

    public function enrolledCount(AttendanceSession $session): int
    {
        return $this->enrolledStudentIds($session)->count();
    }

    public function absentDisplayCount(AttendanceSession $session): int
    {
        if (in_array($session->status, ['closed', 'expired'], true)) {
            return $this->distinctStatusCount($session, 'absent');
        }

        $threshold = $session->started_at->copy()->addMinutes($this->absentAfterMinutes($session));
        if (Carbon::now()->lt($threshold)) {
            return 0;
        }

        $enrolled = $this->enrolledCount($session);
        $recorded = $this->distinctStudentCountWithRecord($session);

        return max(0, $enrolled - $recorded);
    }

    protected function distinctStudentCountWithRecord(AttendanceSession $session): int
    {
        return (int) $session->attendanceRecords()->distinct('student_id')->count('student_id');
    }

    protected function distinctStatusCount(AttendanceSession $session, string $status): int
    {
        return (int) $session->attendanceRecords()
            ->where('status', $status)
            ->distinct('student_id')
            ->count('student_id');
    }

    /**
     * Export / summary: when there is no row yet, whether to treat as absent (empty string = not yet).
     */
    public function statusForExportWithoutRecord(AttendanceSession $session): string
    {
        if (in_array($session->status, ['closed', 'expired'], true)) {
            return 'absent';
        }

        $threshold = $session->started_at->copy()->addMinutes($this->absentAfterMinutes($session));
        if (Carbon::now()->lt($threshold)) {
            return '';
        }

        return 'absent';
    }
}
