<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    public const DAY_PATTERNS = ['MWF', 'TTH'];

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
            default => [$pattern],
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

        return $patterns;
    }

    public static function primaryPatternForDate(Carbon $date): string
    {
        $patterns = self::dayPatternsForDate($date);

        return $patterns[0] ?? 'MWF';
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
        $startTime = Carbon::parse($this->start_time)->subMinutes($toleranceMinutes);
        $endTime = Carbon::parse($this->end_time);

        return $now->between($startTime, $endTime);
    }

    public function getTimeRangeAttribute()
    {
        return Carbon::parse($this->start_time)->format('g:i A').' - '.
               Carbon::parse($this->end_time)->format('g:i A');
    }

    public function getFullScheduleAttribute()
    {
        return "{$this->day_of_week} {$this->time_range} - {$this->room}";
    }
}
