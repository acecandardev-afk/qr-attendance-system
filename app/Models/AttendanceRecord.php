<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_session_id',
        'student_id',
        'status',
        'marked_at',
        'ip_address',
        'network_identifier',
        'remarks',
    ];

    protected $casts = [
        'marked_at' => 'datetime',
    ];

    // Relationships
    public function attendanceSession()
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // Scopes
    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeExcused($query)
    {
        return $query->where('status', 'excused');
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeBySession($query, $sessionId)
    {
        return $query->where('attendance_session_id', $sessionId);
    }

    // Helper Methods
    public function isPresent()
    {
        return $this->status === 'present';
    }

    public function isLate()
    {
        return $this->status === 'late';
    }

    public function isAbsent()
    {
        return $this->status === 'absent';
    }

    public function isExcused()
    {
        return $this->status === 'excused';
    }
}