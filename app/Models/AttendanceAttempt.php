<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_session_id',
        'student_id',
        'session_token',
        'result',
        'ip_address',
        'network_identifier',
        'error_message',
        'user_agent',
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
    public function scopeSuccess($query)
    {
        return $query->where('result', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('result', '!=', 'success');
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByResult($query, $result)
    {
        return $query->where('result', $result);
    }

    public function scopeRecentAttempts($query, $studentId, $minutes = 1)
    {
        return $query->where('student_id', $studentId)
                    ->where('created_at', '>=', now()->subMinutes($minutes));
    }

    // Helper Methods
    public function isSuccess()
    {
        return $this->result === 'success';
    }

    public function isFailed()
    {
        return $this->result !== 'success';
    }
}