@extends('layouts.app')

@section('title', 'Active Attendance Session')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('faculty.sessions.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
            ← Back to Schedules
        </a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Active Attendance Session</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- QR Code Display -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">QR Code</h2>
            
            <div class="flex flex-col items-center">
                <!-- Session Status -->
                <div class="w-full mb-4">
                    <div id="status-indicator" class="flex items-center justify-center p-3 rounded-lg bg-green-100">
                        <span class="flex h-3 w-3 relative mr-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                        </span>
                        <span class="text-green-800 font-semibold">Session Active</span>
                    </div>
                </div>

                <!-- QR Code Image (auto-generated when session started) -->
                <div class="bg-gray-50 p-6 rounded-lg border-4 border-gray-300">
                    <img src="{{ $qrCodeUrl }}" alt="Attendance QR Code - Scan to mark attendance" class="w-64 h-64 object-contain">
                </div>

                <!-- Timer (hours : minutes : seconds only, no milliseconds) -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600 mb-2">Time Remaining</p>
                    <p id="countdown" class="text-4xl font-bold text-gray-800">
                        <span id="hours">0</span>:<span id="minutes">--</span>:<span id="seconds">--</span>
                    </p>
                    <p class="text-xs text-gray-500 mt-2">Session expires at {{ $session->expires_at->format('g:i A') }}</p>
                </div>

                <!-- Close Session Button -->
                <div class="mt-6">
                    @include('partials.confirm-action', [
                        'action' => route('faculty.sessions.close', $session->id),
                        'title' => 'Close this attendance session?',
                        'message' => 'Students will no longer be able to scan the QR code for this session.',
                        'trigger' => 'Close Session',
                        'confirm' => 'Close session',
                        'spoof' => false,
                        'wrapperClass' => 'block',
                        'triggerClass' => 'bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg font-semibold',
                    ])
                </div>
            </div>
        </div>

        <!-- Session Information + Manual Attendance -->
        <div class="space-y-6">
            <!-- Session Details -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Session Details</h2>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-600">Course</p>
                        <p class="font-semibold text-gray-800">{{ $session->schedule->course->name }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-600">Section</p>
                        <p class="font-semibold text-gray-800">{{ $session->schedule->section->name }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-600">Room</p>
                        <p class="font-semibold text-gray-800">{{ $session->schedule->room ?? 'Not specified' }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-600">Started At</p>
                        <p class="font-semibold text-gray-800">{{ $session->started_at->format('F j, Y - g:i A') }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-600">Expires At</p>
                        <p class="font-semibold text-gray-800">{{ $session->expires_at->format('g:i A') }}</p>
                    </div>
                </div>
            </div>

            <!-- Attendance Statistics -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Attendance Statistics</h2>
                
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center">
                        <p id="total-count" class="text-3xl font-bold text-gray-800">{{ $session->attendance_count }}</p>
                        <p class="text-sm text-gray-600">Total</p>
                    </div>
                    <div class="text-center">
                        <p id="present-count" class="text-3xl font-bold text-green-600">{{ $session->present_count }}</p>
                        <p class="text-sm text-gray-600">Present</p>
                    </div>
                    <div class="text-center">
                        <p id="late-count" class="text-3xl font-bold text-yellow-600">{{ $session->late_count }}</p>
                        <p class="text-sm text-gray-600">Late</p>
                    </div>
                </div>
            </div>

            <!-- Recent Attendance -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Attendance</h2>
                
                <div id="recent-attendance" class="space-y-2 max-h-64 overflow-y-auto">
                    @forelse($session->attendanceRecords->sortByDesc('marked_at')->take(10) as $record)
                        <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                            <span class="text-sm text-gray-800">{{ $record->student->full_name }}</span>
                            <span class="text-xs px-2 py-1 rounded 
                                @if($record->status === 'present') bg-green-100 text-green-800
                                @elseif($record->status === 'late') bg-yellow-100 text-yellow-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($record->status) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm text-center py-4">No attendance records yet</p>
                    @endforelse
                </div>
            </div>

            <!-- Manual Attendance -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Manual Attendance</h2>
                    <p class="text-sm text-gray-500">
                        Use this if some students cannot scan the QR (no device / no internet).
                    </p>
                </div>

                <form method="POST" action="{{ route('faculty.sessions.attendance.manual.bulk', $session->id) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Target</label>
                        <select name="target" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <option value="all">All students</option>
                            <option value="unmarked">Only unmarked students</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Set status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <option value="present">Present</option>
                            <option value="late">Late</option>
                            <option value="excused">Excused</option>
                            <option value="absent">Absent (clear records)</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-md text-sm font-semibold">
                            Apply Bulk Update
                        </button>
                    </div>
                </form>

                @if($students->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Set Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($students as $student)
                                    @php
                                        $existing = $session->attendanceRecords->firstWhere('student_id', $student->id);
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-3 text-gray-900">{{ $student->user_id }}</td>
                                        <td class="px-4 py-3 text-gray-900">{{ $student->full_name }}</td>
                                        <td class="px-4 py-3">
                                            @if($existing)
                                                <span class="px-2 py-1 rounded-full text-xs font-semibold
                                                    @if($existing->status === 'present') bg-green-100 text-green-800
                                                    @elseif($existing->status === 'late') bg-yellow-100 text-yellow-800
                                                    @elseif($existing->status === 'excused') bg-blue-100 text-blue-800
                                                    @else bg-gray-100 text-gray-800
                                                    @endif">
                                                    {{ ucfirst($existing->status) }}
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-500">Absent (no record)</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <form method="POST" action="{{ route('faculty.sessions.attendance.manual', $session->id) }}" class="flex items-center space-x-2">
                                                @csrf
                                                <input type="hidden" name="student_id" value="{{ $student->id }}">
                                                <select name="status" class="px-2 py-1 border border-gray-300 rounded-md text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="">Absent (clear)</option>
                                                    <option value="present" @if($existing && $existing->status === 'present') selected @endif>Present</option>
                                                    <option value="late" @if($existing && $existing->status === 'late') selected @endif>Late</option>
                                                    <option value="excused" @if($existing && $existing->status === 'excused') selected @endif>Excused</option>
                                                </select>
                                                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-3 py-1 rounded text-xs font-semibold">
                                                    Save
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500 text-sm">No enrolled students found for this section.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let remainingSeconds = {{ $remainingSeconds }};
    const sessionId = {{ $session->id }};
    let countdownInterval = null;
    let statusInterval = null;

    function stopAllIntervals() {
        if (countdownInterval) clearInterval(countdownInterval);
        if (statusInterval) clearInterval(statusInterval);
        countdownInterval = null;
        statusInterval = null;
    }

    function showExpiredOrClosed(isExpired) {
        stopAllIntervals();
        document.getElementById('countdown').innerHTML = '<span class="text-red-600">EXPIRED</span>';
        document.getElementById('status-indicator').innerHTML = '<span class="text-red-800 font-semibold">' + (isExpired ? 'Session Expired' : 'Session Closed') + '</span>';
        document.getElementById('status-indicator').className = 'flex items-center justify-center p-3 rounded-lg bg-red-100';
    }

    // Countdown timer (hours, minutes, seconds only — no milliseconds)
    function updateCountdown() {
        if (remainingSeconds <= 0) {
            showExpiredOrClosed(true);
            return;
        }

        const hours = Math.floor(remainingSeconds / 3600);
        const minutes = Math.floor((remainingSeconds % 3600) / 60);
        const seconds = remainingSeconds % 60;

        document.getElementById('hours').textContent = String(hours).padStart(2, '0');
        document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
        document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');

        remainingSeconds--;
    }

    // Update session status and attendance count (no reload — update UI only)
    function updateStatus() {
        fetch(`/faculty/sessions/${sessionId}/status`)
            .then(response => response.ok ? response.json() : Promise.reject(new Error('Network error')))
            .then(data => {
                document.getElementById('total-count').textContent = data.attendance_count;
                document.getElementById('present-count').textContent = data.present_count;
                document.getElementById('late-count').textContent = data.late_count;

                if (data.is_expired || data.status !== 'active') {
                    showExpiredOrClosed(data.is_expired);
                }
            })
            .catch(error => console.error('Error fetching status:', error));
    }

    // Start intervals
    countdownInterval = setInterval(updateCountdown, 1000);
    statusInterval = setInterval(updateStatus, 5000);

    updateCountdown();
    updateStatus();
</script>
@endpush
@endsection