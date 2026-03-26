@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <p class="text-xs uppercase tracking-widest text-indigo-600 font-semibold">NORSU-Guihulngan</p>
        <h1 class="text-4xl font-extrabold text-slate-900 mt-2">Admin Dashboard</h1>
        <p class="text-slate-600 mt-2">Overview of system activity, attendance health, and academic operations.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-gradient-to-br from-slate-900 to-slate-700 text-white rounded-xl shadow p-5">
            <p class="text-xs uppercase tracking-wide text-slate-200">Total Users</p>
            <p class="mt-2 text-3xl font-extrabold">{{ $stats['total_users'] ?? 0 }}</p>
        </div>
        <div class="bg-gradient-to-br from-blue-600 to-indigo-600 text-white rounded-xl shadow p-5">
            <p class="text-xs uppercase tracking-wide text-blue-100">Students</p>
            <p class="mt-2 text-3xl font-extrabold">{{ $stats['total_students'] ?? 0 }}</p>
        </div>
        <div class="bg-gradient-to-br from-emerald-600 to-green-600 text-white rounded-xl shadow p-5">
            <p class="text-xs uppercase tracking-wide text-emerald-100">Faculty</p>
            <p class="mt-2 text-3xl font-extrabold">{{ $stats['total_faculty'] ?? 0 }}</p>
        </div>
        <div class="bg-gradient-to-br from-violet-600 to-purple-600 text-white rounded-xl shadow p-5">
            <p class="text-xs uppercase tracking-wide text-violet-100">Admins</p>
            <p class="mt-2 text-3xl font-extrabold">{{ $stats['total_admins'] ?? 0 }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Entities summary -->
        <div class="bg-white rounded-xl shadow p-6 space-y-3">
            <h2 class="text-lg font-bold text-slate-800 mb-2">Academic Overview</h2>
            <div class="flex justify-between text-sm text-slate-700">
                <span>Departments</span>
                <span class="font-semibold">{{ $stats['total_departments'] ?? 0 }}</span>
            </div>
            <div class="flex justify-between text-sm text-slate-700">
                <span>Courses</span>
                <span class="font-semibold">{{ $stats['total_courses'] ?? 0 }}</span>
            </div>
            <div class="flex justify-between text-sm text-slate-700">
                <span>Sections</span>
                <span class="font-semibold">{{ $stats['total_sections'] ?? 0 }}</span>
            </div>
            <div class="flex justify-between text-sm text-slate-700">
                <span>Schedules</span>
                <span class="font-semibold">{{ $stats['total_schedules'] ?? 0 }}</span>
            </div>
            <div class="flex justify-between text-sm text-slate-700">
                <span>Active Enrollments</span>
                <span class="font-semibold">{{ $stats['active_enrollments'] ?? 0 }}</span>
            </div>
        </div>

        <!-- Today summary -->
        <div class="bg-white rounded-xl shadow p-6 space-y-3">
            <h2 class="text-lg font-bold text-slate-800 mb-2">Today</h2>
            <div class="flex justify-between text-sm text-slate-700">
                <span>Sessions Started</span>
                <span class="font-semibold">{{ $stats['sessions_today'] ?? 0 }}</span>
            </div>
            <div class="flex justify-between text-sm text-slate-700">
                <span>Attendance Marked</span>
                <span class="font-semibold">{{ $stats['attendance_today'] ?? 0 }}</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Users -->
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-4">Recent Users</h2>
            @if($recentUsers->count())
                <div class="divide-y divide-gray-100">
                    @foreach($recentUsers as $user)
                        <div class="py-3 flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-slate-800">{{ $user->full_name }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ strtoupper($user->role) }} • ID: {{ $user->user_id }}
                                </p>
                            </div>
                            <p class="text-xs text-slate-500">
                                Joined {{ $user->created_at->diffForHumans() }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm">No recent users found.</p>
            @endif
        </div>

        <!-- Recent Sessions -->
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-4">Recent Attendance Sessions</h2>
            @if($recentSessions->count())
                <div class="divide-y divide-gray-100">
                    @foreach($recentSessions as $session)
                        <div class="py-3 flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-slate-800">
                                    {{ $session->schedule->course->name ?? 'Unknown Course' }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ $session->schedule->section->name ?? 'Unknown Section' }}
                                    • {{ optional($session->started_at)->format('M j, Y g:i A') }}
                                </p>
                                <p class="text-xs text-slate-500 mt-1">
                                    Faculty: {{ $session->faculty->full_name ?? 'N/A' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold
                                    @if($session->status === 'active') bg-green-100 text-green-800
                                    @elseif($session->status === 'closed') bg-gray-100 text-gray-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst($session->status) }}
                                </span>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $session->attendanceRecords->count() }} records
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-gray-500 text-sm">No recent sessions found.</p>
            @endif
        </div>
    </div>
</div>
@endsection

