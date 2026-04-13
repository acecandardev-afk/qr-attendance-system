<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use HasFactory, SoftDeletes;

    public const SEMESTERS = ['1st Sem', '2nd Sem'];

    protected $fillable = [
        'name',
        'department_id',
        'year_level',
        'semester',
        'school_year',
        'status',
    ];

    // Relationships
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments', 'section_id', 'student_id')
            ->wherePivot('status', 'enrolled');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCurrentSchoolYear($query, $schoolYear)
    {
        return $query->where('school_year', $schoolYear);
    }

    public function scopeCurrentSemester($query, $semester)
    {
        return $query->where('semester', $semester);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    // Helper Methods
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function getFullNameAttribute()
    {
        return "{$this->name} ({$this->school_year} - {$this->semester})";
    }
}