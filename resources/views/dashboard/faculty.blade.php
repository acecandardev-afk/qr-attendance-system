@extends('layouts.app')

@section('title', 'Faculty Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Faculty Dashboard</h1>
        <p class="text-gray-600 mt-2">Welcome back, {{ $user->full_name }}</p>
    </div>

    <!-- Today's Schedule -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Today's Schedule</h2>
        
        @php
            $todaySchedules = $user->facultySchedules()->today()->active()->with('course', 'section')->get();
        @endphp

        @if($todaySchedules->count() > 0)
            <div class="space-y-4">
                @foreach($todaySchedules as $schedule)
                    @php
                        $activeSession = \App\Models\AttendanceSession::where('schedule_id', $schedule->id)
                            ->where('status', 'active')
                            ->where('expires_at', '>', now())
                            ->first();
                    @endphp
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-semibold text-gray-800">{{ $schedule->course->name }}</h3>
                                <p class="text-sm text-gray-600">{{ $schedule->section->name }}</p>
                                <p class="text-sm text-gray-500 mt-1">
                                    {{ $schedule->time_range }} - {{ $schedule->room }}
                                </p>
                            </div>

                            @if($activeSession)
                                <a href="{{ route('faculty.sessions.show', $activeSession->id) }}"
                                   class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded text-sm">
                                    View Session
                                </a>
                            @else
                                <form action="{{ route('faculty.sessions.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="schedule_id" value="{{ $schedule->id }}">
                                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm">
                                        Start Attendance
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500">No classes scheduled for today.</p>
        @endif
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-600">Total Schedules</h3>
            <p class="text-3xl font-bold text-gray-800 mt-2">{{ $user->facultySchedules()->active()->count() }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-600">Active Sessions Today</h3>
            <p class="text-3xl font-bold text-gray-800 mt-2">0</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-600">Total Students</h3>
            <p class="text-3xl font-bold text-gray-800 mt-2">{{ $user->facultySchedules()->with('section.enrollments')->get()->pluck('section.enrollments')->flatten()->unique('student_id')->count() }}</p>
        </div>
    </div>
</div>
@endsection