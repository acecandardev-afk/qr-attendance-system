<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AttendanceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_token',
        'schedule_id',
        'faculty_id',
        'started_at',
        'expires_at',
        'closed_at',
        'status',
        'qr_code_path',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Relationships
    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function attendanceAttempts()
    {
        return $this->hasMany(AttendanceAttempt::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByFaculty($query, $facultyId)
    {
        return $query->where('faculty_id', $facultyId);
    }

    public function scopeBySchedule($query, $scheduleId)
    {
        return $query->where('schedule_id', $scheduleId);
    }

    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', Carbon::now())
                    ->where('status', 'active');
    }

    // Helper Methods
    public function isActive()
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    public function isExpired()
    {
        return Carbon::now()->greaterThan($this->expires_at);
    }

    public function isClosed()
    {
        return $this->status === 'closed';
    }

    public function canAcceptAttendance()
    {
        return $this->isActive() && !$this->isExpired() && !$this->isClosed();
    }

    public function getRemainingTimeAttribute()
    {
        if ($this->isExpired()) {
            return 0;
        }

        return Carbon::now()->diffInSeconds($this->expires_at, false);
    }

    public function getAttendanceCountAttribute()
    {
        return $this->attendanceRecords()->count();
    }

    public function getPresentCountAttribute()
    {
        return $this->attendanceRecords()->where('status', 'present')->count();
    }

    public function getLateCountAttribute()
    {
        return $this->attendanceRecords()->where('status', 'late')->count();
    }

    // Static Methods
    public static function generateToken()
    {
        return Str::random(64);
    }
}