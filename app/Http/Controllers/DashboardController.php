<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Enrollment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'faculty') {
            return $this->facultyDashboard($user);
        }

        return match ($user->role) {
            'admin' => view('dashboard.admin', compact('user')),
            'student' => view('dashboard.student', compact('user')),
            default => abort(403, 'You do not have permission to open this page.'),
        };
    }

    private function facultyDashboard($user)
    {
        $scheduleIds = $user->facultySchedules()->active()->pluck('id');

        // Stat cards
        $totalSchedules = $scheduleIds->count();
        $activeSessionsToday = AttendanceSession::whereIn('schedule_id', $scheduleIds)
            ->where('status', 'active')
            ->whereDate('started_at', today())
            ->count();
        $sectionIds = $user->facultySchedules()->active()->distinct('section_id')->pluck('section_id');
        $totalStudents = Enrollment::whereIn('section_id', $sectionIds)
            ->where('status', 'enrolled')
            ->distinct('student_id')
            ->count('student_id');

        // Daily attendance chart — last 14 days
        $days = collect();
        $present = collect();
        $late = collect();
        $absent = collect();

        for ($i = 13; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $days->push($date->format('M d'));

            $sessionIds = AttendanceSession::whereIn('schedule_id', $scheduleIds)
                ->whereDate('started_at', $date)
                ->pluck('id');

            $records = AttendanceRecord::whereIn('attendance_session_id', $sessionIds)->get();

            $present->push($records->where('status', 'present')->count());
            $late->push($records->where('status', 'late')->count());
            $absent->push($records->where('status', 'absent')->count());
        }

        return view('dashboard.faculty', compact(
            'user',
            'totalSchedules',
            'activeSessionsToday',
            'totalStudents',
            'days',
            'present',
            'late',
            'absent'
        ));
    }
}
