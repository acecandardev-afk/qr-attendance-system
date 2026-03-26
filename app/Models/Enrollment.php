<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'section_id',
        'school_year',
        'semester',
        'status',
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Class schedules this enrollment applies to (specific courses / time slots).
     * If empty, the student is treated as enrolled for the whole section (legacy behavior).
     */
    public function schedules()
    {
        return $this->belongsToMany(Schedule::class, 'enrollment_schedule')->withTimestamps();
    }

    /**
     * Enrollments that count for attendance in this class schedule.
     * If the enrollment has no linked schedules, it applies to all schedules in the section.
     */
    public function scopeEligibleForSchedule($query, Schedule $schedule)
    {
        return $query->where('section_id', $schedule->section_id)
            ->where('status', 'enrolled')
            ->where(function ($q) use ($schedule) {
                $q->whereDoesntHave('schedules')
                    ->orWhereHas('schedules', fn ($q) => $q->where('schedules.id', $schedule->id));
            });
    }

    // Scopes
    public function scopeEnrolled($query)
    {
        return $query->where('status', 'enrolled');
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeBySection($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    public function scopeCurrentSchoolYear($query, $schoolYear)
    {
        return $query->where('school_year', $schoolYear);
    }

    public function scopeCurrentSemester($query, $semester)
    {
        return $query->where('semester', $semester);
    }

    // Helper Methods
    public function isEnrolled()
    {
        return $this->status === 'enrolled';
    }

    public function isDropped()
    {
        return $this->status === 'dropped';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }
}