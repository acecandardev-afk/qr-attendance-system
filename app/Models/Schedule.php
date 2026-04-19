<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    public const DAY_PATTERNS = ['MWF', 'TTH', 'SAT', 'SUN'];

    protected $fillable = [
        'course_id',
        'section_id',
        'faculty_id',
        'day_of_week',
        'start_time',
        'end_time',
        'room',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public static function normalizeDayPattern(?string $day): ?string
    {
        if ($day === null) {
            return null;
        }

        $d = trim($day);
        if ($d === '') {
            return $d;
        }

        return match ($d) {
            'MWF', 'Mon', 'Monday', 'Wed', 'Wednesday', 'Fri', 'Friday' => 'MWF',
            'TTH', 'Tue', 'Tuesday', 'Thu', 'Thursday' => 'TTH',
            'SAT', 'Sat', 'Saturday' => 'SAT',
            'SUN', 'Sun', 'Sunday' => 'SUN',
            default => $d,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function dbValuesForDayPattern(string $pattern): array
    {
        $p = self::normalizeDayPattern($pattern) ?? $pattern;

        return match ($p) {
            'MWF' => ['MWF', 'Monday', 'Wednesday', 'Friday'],
            'TTH' => ['TTH', 'Tuesday', 'Thursday'],
            'SAT' => ['SAT', 'Saturday'],
            'SUN' => ['SUN', 'Sunday'],
            default => [$p],
        };
    }

    public function getDayOfWeekAttribute($value)
    {
        return self::normalizeDayPattern(is_string($value) ? $value : null) ?? $value;
    }

    public function setDayOfWeekAttribute($value)
    {
        $this->attributes['day_of_week'] = self::normalizeDayPattern(is_string($value) ? $value : null) ?? $value;
    }

    // Relationships
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function attendanceSessions()
    {
        return $this->hasMany(AttendanceSession::class);
    }

    public function enrollments()
    {
        return $this->belongsToMany(Enrollment::class, 'enrollment_schedule')->withTimestamps();
    }

    // Scopes
    public function scopeOrderByDayPattern($query)
    {
        return $query->orderByRaw("CASE day_of_week
            WHEN 'MWF' THEN 1
            WHEN 'TTH' THEN 2
            WHEN 'SAT' THEN 3
            WHEN 'SUN' THEN 4
            WHEN 'Saturday' THEN 3
            WHEN 'Sunday' THEN 4
            ELSE 99 END");
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByFaculty($query, $facultyId)
    {
        return $query->where('faculty_id', $facultyId);
    }

    public function scopeByDay($query, $day)
    {
        return $query->whereIn('day_of_week', self::dbValuesForDayPattern((string) $day));
    }

    /**
     * Day patterns that apply to the given calendar date (Mon–Sun).
     *
     * @return array<int, string>
     */
    public static function dayPatternsForDate(Carbon $date): array
    {
        $d = $date->dayOfWeek;

        $patterns = [];
        if (in_array($d, [1, 3, 5], true)) {
            $patterns[] = 'MWF';
        }
        if (in_array($d, [2, 4], true)) {
            $patterns[] = 'TTH';
        }
        if ($date->isSaturday()) {
            $patterns[] = 'SAT';
        }
        if ($date->isSunday()) {
            $patterns[] = 'SUN';
        }

        return $patterns;
    }

    public static function primaryPatternForDate(Carbon $date): string
    {
        $patterns = self::dayPatternsForDate($date);

        return $patterns[0] ?? 'MWF';
    }

    /**
     * Carbon/PHP weekday: 0 = Sunday … 6 = Saturday.
     *
     * @return array<int, int>
     */
    public static function weekdayIndexesForPattern(string $pattern): array
    {
        $p = self::normalizeDayPattern($pattern) ?? $pattern;

        return match ($p) {
            'MWF' => [1, 3, 5],
            'TTH' => [2, 4],
            'SAT' => [6],
            'SUN' => [0],
            default => [],
        };
    }

    public static function hmToMinutes(string $hm): int
    {
        $c = Carbon::createFromFormat('H:i', trim($hm));

        return $c->hour * 60 + $c->minute;
    }

    /**
     * Minute spans per calendar weekday for this class pattern and clock times.
     * End time is treated as inclusive on the minute grid; each span is half-open [a, b) with
     * b = endMinute + 1 so another class may start the minute after the inclusive end
     * (e.g. ends 12:00 → next may start 12:01). Overnight slots split across weekdays.
     *
     * @return list<array{d: int, a: int, b: int}>
     */
    public static function minuteSegmentsForDayPattern(string $pattern, string $startHm, string $endHm): array
    {
        $startMin = self::hmToMinutes($startHm);
        $endMin = self::hmToMinutes($endHm);
        $days = self::weekdayIndexesForPattern($pattern);
        $out = [];
        foreach ($days as $d) {
            if ($endMin > $startMin) {
                $out[] = ['d' => $d, 'a' => $startMin, 'b' => $endMin + 1];
            } else {
                $out[] = ['d' => $d, 'a' => $startMin, 'b' => 1440];
                $out[] = ['d' => ($d + 1) % 7, 'a' => 0, 'b' => $endMin + 1];
            }
        }

        return $out;
    }

    /** Whether two half-open same-day intervals [a1,b1) and [a2,b2) overlap in time. */
    public static function sameDayHalfOpenIntervalsOverlap(int $a1, int $b1, int $a2, int $b2): bool
    {
        return max($a1, $a2) < min($b1, $b2);
    }

    /**
     * @param  list<array{d: int, a: int, b: int}>  $segmentsA
     * @param  list<array{d: int, a: int, b: int}>  $segmentsB
     */
    public static function minuteSegmentListsConflict(array $segmentsA, array $segmentsB): bool
    {
        foreach ($segmentsA as $s1) {
            foreach ($segmentsB as $s2) {
                if ($s1['d'] !== $s2['d']) {
                    continue;
                }
                if (self::sameDayHalfOpenIntervalsOverlap($s1['a'], $s1['b'], $s2['a'], $s2['b'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * True if an active schedule in this section already overlaps this slot on any shared
     * weekday (end 12:00 and start 12:00 conflict; start 12:01 is allowed).
     */
    public static function sectionHasScheduleTimeConflict(
        int $sectionId,
        string $dayPattern,
        string $startTimeHhMm,
        string $endTimeHhMm,
        ?int $ignoreScheduleId = null,
    ): bool {
        $proposed = self::minuteSegmentsForDayPattern($dayPattern, $startTimeHhMm, $endTimeHhMm);

        $q = self::query()
            ->where('section_id', $sectionId)
            ->where('status', 'active')
            ->whereNull('deleted_at');

        if ($ignoreScheduleId !== null) {
            $q->where('id', '!=', $ignoreScheduleId);
        }

        foreach ($q->get() as $other) {
            $rawDay = $other->getRawOriginal('day_of_week');
            if ($rawDay === null) {
                $rawDay = $other->day_of_week;
            }
            $existing = self::minuteSegmentsForDayPattern(
                (string) $rawDay,
                $other->start_time->format('H:i'),
                $other->end_time->format('H:i'),
            );
            if (self::minuteSegmentListsConflict($proposed, $existing)) {
                return true;
            }
        }

        return false;
    }

    public function scopeToday($query)
    {
        $patterns = self::dayPatternsForDate(Carbon::now());
        $dbValues = collect($patterns)
            ->flatMap(fn ($p) => self::dbValuesForDayPattern($p))
            ->unique()
            ->values()
            ->all();

        return $query->whereIn('day_of_week', $dbValues);
    }

    public function scopeBySection($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    // Helper Methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isToday()
    {
        return in_array($this->day_of_week, self::dayPatternsForDate(Carbon::now()), true);
    }

    public function isHappeningNow($toleranceMinutes = 15)
    {
        if (! $this->isToday()) {
            return false;
        }

        $now = Carbon::now();
        $startTime = Carbon::parse($this->start_time)->setDateFrom($now)->subMinutes($toleranceMinutes);
        $endTime = Carbon::parse($this->end_time)->setDateFrom($now);

        if ($endTime->lessThanOrEqualTo($startTime)) {
            $endTime->addDay();
        }

        return $now->between($startTime, $endTime);
    }

    public function getTimeRangeAttribute()
    {
        return Carbon::parse($this->start_time)->format('g:i A').' - '.
               Carbon::parse($this->end_time)->format('g:i A');
    }

    public function getFullScheduleAttribute()
    {
        $base = "{$this->day_of_week} {$this->time_range}";

        return filled($this->room) ? "{$base} - {$this->room}" : $base;
    }
}
