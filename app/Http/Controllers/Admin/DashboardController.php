<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_students' => User::students()->count(),
            'total_faculty' => User::faculty()->count(),
            'total_admins' => User::admins()->count(),
            'total_departments' => Department::count(),
            'total_courses' => Course::count(),
            'total_sections' => Section::count(),
            'total_schedules' => Schedule::count(),
            'active_enrollments' => Enrollment::enrolled()->count(),
            'sessions_today' => AttendanceSession::whereDate('started_at', Carbon::today())->count(),
            'attendance_today' => AttendanceRecord::whereDate('marked_at', Carbon::today())->count(),
        ];

        // Recent activities
        $recentUsers = User::latest()->take(5)->get();
        $recentSessions = AttendanceSession::with(['faculty', 'schedule.course', 'schedule.section'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentUsers', 'recentSessions'));
    }
}
