<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements CanResetPasswordContract
{
    use CanResetPassword;
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'user_id',
        'email',
        'password',
        'role',
        'first_name',
        'middle_name',
        'last_name',
        'year_level',
        'address',
        'age',
        'birthday',
        'department_id',
        'employment_status',
        'check_in_code_valid_minutes',
        'late_after_minutes',
        'absent_after_minutes',
        'status',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birthday' => 'date',
        'check_in_code_valid_minutes' => 'integer',
        'late_after_minutes' => 'integer',
        'absent_after_minutes' => 'integer',
    ];

    // Relationships
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class, 'student_id');
    }

    public function facultySchedules()
    {
        return $this->hasMany(Schedule::class, 'faculty_id');
    }

    public function attendanceSessions()
    {
        return $this->hasMany(AttendanceSession::class, 'faculty_id');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'student_id');
    }

    public function attendanceAttempts()
    {
        return $this->hasMany(AttendanceAttempt::class, 'student_id');
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function getFullNameWithoutMiddleAttribute()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getMiddleInitialAttribute(): string
    {
        $m = trim((string) $this->middle_name);

        return $m !== '' ? strtoupper(mb_substr($m, 0, 1)).'.' : '—';
    }

    /**
     * Friendly name for students (no email) — e.g. class lists.
     */
    public function getTeacherDisplayNameAttribute(): string
    {
        return $this->full_name_without_middle;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeStudents($query)
    {
        return $query->where('role', 'student');
    }

    public function scopeFaculty($query)
    {
        return $query->where('role', 'faculty');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    // Helper Methods
    public function isStudent()
    {
        return $this->role === 'student';
    }

    public function isFaculty()
    {
        return $this->role === 'faculty';
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isActive()
    {
        return $this->status === 'active';
    }
}
