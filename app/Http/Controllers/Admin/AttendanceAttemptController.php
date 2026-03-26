<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceAttempt;
use Illuminate\Http\Request;

class AttendanceAttemptController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendanceAttempt::with(['student', 'attendanceSession.schedule.course', 'attendanceSession.schedule.section'])
            ->orderByDesc('created_at');

        if ($request->filled('result')) {
            $query->where('result', $request->result);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $attempts = $query->paginate(25)->withQueryString();

        return view('admin.attendance_attempts.index', compact('attempts'));
    }
}
