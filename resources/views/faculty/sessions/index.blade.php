@extends('layouts.app')

@section('title', 'Attendance Schedules')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Attendance Schedules</h1>
        <p class="text-gray-600 mt-2">Start and manage attendance for your classes</p>
    </div>

    <!-- Emergency Class -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h2 class="text-xl font-bold text-gray-800">Start Emergency Class</h2>
            <p class="text-sm text-gray-500">For make-up classes or unscheduled attendance.</p>
        </div>

        <form method="POST" action="{{ route('faculty.sessions.store.ad-hoc') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Class Template</label>
                <select name="template_schedule_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">Select class</option>
                    @foreach($adHocTemplates as $template)
                        <option value="{{ $template->id }}">
                            {{ $template->course?->name ?? 'Subject removed' }} - {{ $template->section?->name ?? 'Section removed' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Duration (minutes)</label>
                <input type="number" name="duration_minutes" min="5" max="180" value="30" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Room (optional)</label>
                <input type="text" name="room" placeholder="e.g. IT-LAB-301" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-semibold">
                    Start Emergency Class
                </button>
            </div>
        </form>
    </div>

    <!-- Today's Schedule -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Today's Schedule ({{ Carbon\Carbon::now()->format('l, F j, Y') }})</h2>
        
        @if($todaySchedules->count() > 0)
            <div class="space-y-4">
                @foreach($todaySchedules as $schedule)
                    @php
                        $activeSession = $schedule->attendanceSessions
                            ->where('status', 'active')
                            ->where('expires_at', '>', now())
                            ->first();
                        $latestSession = $schedule->attendanceSessions->first();
                    @endphp

                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-800 text-lg">{{ $schedule->course?->name ?? 'Subject removed' }}</h3>
                                <p class="text-sm text-gray-600">{{ $schedule->section?->name ?? 'Section removed' }}</p>
                                <p class="text-sm text-gray-500 mt-1">
                                    <span class="font-medium">Time:</span> {{ $schedule->time_range }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    <span class="font-medium">Room:</span> {{ $schedule->room ?? 'Not specified' }}
                                </p>
                                
                                @if($latestSession)
                                    <p class="text-xs text-gray-400 mt-2">
                                        Last session: {{ $latestSession->started_at->format('g:i A') }}
                                        ({{ $latestSession->attendance_count }} students marked)
                                    </p>
                                @endif
                            </div>

                            <div class="ml-4">
                                @if($activeSession)
                                    <a href="{{ route('faculty.sessions.show', $activeSession->id) }}" 
                                       class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded text-sm inline-block">
                                        View Active Session
                                    </a>
                                @else
                                    <form action="{{ route('faculty.sessions.store') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="schedule_id" value="{{ $schedule->id }}">
                                        <button type="submit" 
                                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm">
                                            Start Attendance
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-gray-500 mt-2">No classes scheduled for today.</p>
            </div>
        @endif
    </div>

    <!-- All Schedules -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Weekly Schedule</h2>
        
        @if($allSchedules->count() > 0)
            <div class="space-y-6">
                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                    @if($allSchedules->has($day))
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-2">{{ $day }}</h3>
                            <div class="space-y-2">
                                @foreach($allSchedules[$day] as $schedule)
                                    <div class="border-l-4 border-blue-500 pl-4 py-2">
                                        <p class="font-medium text-gray-800">{{ $schedule->course?->name ?? 'Subject removed' }}</p>
                                        <p class="text-sm text-gray-600">{{ $schedule->section?->name ?? 'Section removed' }} • {{ $schedule->time_range }} • {{ $schedule->room }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            <p class="text-gray-500">No schedules available.</p>
        @endif
    </div>
</div>
@endsection