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
                    <img src="{!! $qrCodeUrl !!}" alt="Attendance QR Code - Scan to mark attendance" class="w-64 h-64 object-contain" width="256" height="256" decoding="async">
                </div>

                <!-- Timer: hours and minutes only -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600 mb-2">Time Remaining</p>
                    <p id="countdown" class="text-4xl font-bold text-gray-800">
                        <span id="hours">0</span>:<span id="minutes">--</span>
                    </p>
                    <p class="text-xs text-gray-500 mt-2">Session expires at {{ $session->expires_at->format('g:i A') }}</p>
                </div>

                <!-- Close Session Button -->
                <div class="mt-6">
                    {!! view('partials.confirm-action', [
                        'action' => route('faculty.sessions.close', $session->id),
                        'title' => 'Close this attendance session?',
                        'message' => 'Students will no longer be able to scan the QR code for this session.',
                        'trigger' => 'Close Session',
                        'confirm' => 'Close session',
                        'confirmPlainPost' => true,
                        'wrapperClass' => 'block',
                        'triggerClass' => 'bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg font-semibold',
                    ])->render() !!}
                </div>
            </div>
        </div>

        <!-- Session Information -->
        <div class="space-y-6">
            <!-- Session Details -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Session Details</h2>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-600">Subject</p>
                        <p class="font-semibold text-gray-800">{{ $session->schedule?->course?->name ?? 'Subject removed' }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-600">Section</p>
                        <p class="font-semibold text-gray-800">{{ $session->schedule?->section?->name ?? 'Section removed' }}</p>
                    </div>
                    
                    @if(filled($session->schedule->room))
                    <div>
                        <p class="text-sm text-gray-600">Room</p>
                        <p class="font-semibold text-gray-800">{{ $session->schedule->room }}</p>
                    </div>
                    @endif

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
                
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="text-center">
                        <p id="enrolled-count" class="text-3xl font-bold text-gray-800">{{ $session->enrolled_count }}</p>
                        <p class="text-sm text-gray-600">Enrolled</p>
                    </div>
                    <div class="text-center">
                        <p id="present-count" class="text-3xl font-bold text-green-600">{{ $session->present_count }}</p>
                        <p class="text-sm text-gray-600">Present</p>
                    </div>
                    <div class="text-center">
                        <p id="late-count" class="text-3xl font-bold text-yellow-600">{{ $session->late_count }}</p>
                        <p class="text-sm text-gray-600">Late</p>
                    </div>
                    <div class="text-center">
                        <p id="absent-count" class="text-3xl font-bold text-red-600">{{ $session->absent_count }}</p>
                        <p class="text-sm text-gray-600">Absent</p>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-3 text-center">Absent is shown after your configured wait from session start, or from finalized records when the session ends.</p>
            </div>

            <!-- Recent Attendance -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Attendance</h2>
                
                <div id="recent-attendance" class="space-y-2 max-h-64 overflow-y-auto">
                    @forelse($session->attendanceRecords->sortByDesc('marked_at')->take(10) as $record)
                        <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                            <span class="text-sm text-gray-800">{{ $record->student?->full_name ?? 'Unknown student' }}</span>
                            <span class="text-xs px-2 py-1 rounded 
                                @if($record->status === 'present') bg-green-100 text-green-800
                                @elseif($record->status === 'late') bg-yellow-100 text-yellow-800
                                @elseif($record->status === 'absent') bg-red-100 text-red-800
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

    // Countdown: hours and minutes only (still ticks every second so the minute rolls correctly)
    function updateCountdown() {
        if (remainingSeconds <= 0) {
            showExpiredOrClosed(true);
            return;
        }

        const hours = Math.floor(remainingSeconds / 3600);
        const minutes = Math.floor((remainingSeconds % 3600) / 60);

        document.getElementById('hours').textContent = String(hours).padStart(2, '0');
        document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');

        remainingSeconds--;
    }

    // Update session status and attendance count (no reload — update UI only)
    function updateStatus() {
        fetch(`/faculty/sessions/${sessionId}/status`)
            .then(response => response.ok ? response.json() : Promise.reject(new Error('Network error')))
            .then(data => {
                document.getElementById('enrolled-count').textContent = data.enrolled_count;
                document.getElementById('present-count').textContent = data.present_count;
                document.getElementById('late-count').textContent = data.late_count;
                document.getElementById('absent-count').textContent = data.absent_count;

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