@extends('layouts.app')

@section('title', 'Mark Attendance')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Mark Attendance</h1>
        <p class="text-gray-600 mt-2">Scan the QR code displayed by your instructor</p>
    </div>

    @if(\Illuminate\Support\Str::startsWith((string) config('app.url'), 'https://'))
        <div class="mb-6 rounded-xl border border-sky-300 bg-sky-50 px-4 py-3 text-sky-950 shadow-sm sm:text-base text-[15px] leading-snug" role="note">
            <p class="font-bold text-sky-900">On your phone — use HTTPS</p>
            <p class="mt-1">Open this app at <span class="font-mono text-sm font-semibold break-all">{{ rtrim((string) config('app.url'), '/') }}</span> (same Wi‑Fi as the classroom PC). Plain <span class="font-mono">http://</span> or the wrong address will break the camera and scanning.</p>
        </div>
    @endif

    @if(!empty($secureScannerUrl))
        <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-amber-950 shadow-sm" role="status">
            <p class="font-semibold">Camera needs a secure (HTTPS) address</p>
            <p class="mt-1 text-sm">You opened this page over plain HTTP. On your phone, use the HTTPS link so the camera can start (required by the browser).</p>
            <p class="mt-3">
                <a href="{{ $secureScannerUrl }}" class="inline-flex items-center rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                    Open scanner on HTTPS
                </a>
            </p>
            <p class="mt-2 text-xs text-amber-900/80">Use the same Wi‑Fi as the classroom PC. If the link fails, ask your instructor to run the app with Caddy (port 9443).</p>
        </div>
    @endif

    <!-- Attendance Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-600">Total</p>
            <p class="text-2xl font-bold text-gray-800">{{ $summary['total'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-600">Present</p>
            <p class="text-2xl font-bold text-green-600">{{ $summary['present'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-600">Late</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $summary['late'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-600">Absent</p>
            <p class="text-2xl font-bold text-red-600">{{ $summary['absent'] }}</p>
        </div>
    </div>

    <!-- QR Scanner -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-2">Scan QR Code</h2>
        @if(request()->secure())
            <p class="mb-4 text-sm text-emerald-800 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">You are on a secure (HTTPS) link — scanning will submit to this site.</p>
        @endif

        <!-- Scanner Container -->
        <div id="scanner-container" class="mb-4">
            <div id="qr-reader" class="mx-auto" style="max-width: 600px;"></div>
        </div>

        <!-- Scanner Controls -->
        <div class="flex justify-center space-x-4 mb-4">
            <button id="start-scanner" 
                    class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold">
                Start Scanner
            </button>
            <button id="stop-scanner" 
                    class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg font-semibold hidden">
                Stop Scanner
            </button>
        </div>

        <!-- Status Messages -->
        <div id="scanner-status" class="text-center text-sm text-gray-600"></div>
        
        <!-- Result Messages -->
        <div id="result-message" class="mt-4 hidden"></div>
    </div>

    <!-- Recent Attendance -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-800">Recent Attendance</h2>
            <a href="{{ route('student.attendance.history', [], false) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                View All →
            </a>
        </div>

        @if($summary['recent_records']->count() > 0)
            <div class="space-y-2">
                @foreach($summary['recent_records'] as $record)
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <div>
                            <p class="font-semibold text-gray-800">{{ $record->attendanceSession?->schedule?->course?->name ?? 'Subject removed' }}</p>
                            <p class="text-sm text-gray-600">{{ $record->attendanceSession?->schedule?->section?->name ?? 'Section removed' }}</p>
                            <p class="text-xs text-gray-500">{{ $record->marked_at->format('M j, Y - g:i A') }}</p>
                        </div>
                        <span class="px-3 py-1 rounded text-sm font-semibold
                            @if($record->status === 'present') bg-green-100 text-green-800
                            @elseif($record->status === 'late') bg-yellow-100 text-yellow-800
                            @elseif($record->status === 'absent') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst($record->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-center py-8">No attendance records yet</p>
        @endif
    </div>
</div>

@push('scripts')
<!-- Include HTML5 QR Code Scanner Library -->
<script src="/vendor/html5-qrcode.min.js"></script>

<script>
    {{-- Same-origin path only — never absolute APP_URL host (fixes phone posting to 127.0.0.1 and "Processing..." hang) --}}
    const ATTENDANCE_SCAN_URL = @json(route('student.attendance.scan', [], false));
    const CSRF_TOKEN = @json(csrf_token());

    let html5QrCode = null;
    let isScanning = false;
    let lastDecodedText = null;
    /** Stops the camera from firing many reads/sec → multiple POSTs for one physical scan */
    let scanInFlight = false;
    let lastSubmittedPayload = null;
    let lastSubmittedAt = 0;
    const SCAN_COOLDOWN_MS = 8000;

    const startButton = document.getElementById('start-scanner');
    const stopButton = document.getElementById('stop-scanner');
    const statusDiv = document.getElementById('scanner-status');
    const resultDiv = document.getElementById('result-message');

    const OFFLINE_QUEUE_KEY = 'attendance_offline_queue_v1';

    function escapeHtml(text) {
        if (text === null || text === undefined) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    // Simple localStorage-based offline queue
    function loadOfflineQueue() {
        try {
            const raw = localStorage.getItem(OFFLINE_QUEUE_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch (e) {
            console.error('Failed to parse offline queue', e);
            return [];
        }
    }

    function saveOfflineQueue(queue) {
        try {
            localStorage.setItem(OFFLINE_QUEUE_KEY, JSON.stringify(queue));
        } catch (e) {
            console.error('Failed to save offline queue', e);
        }
    }

    function enqueueScan(qrData) {
        const queue = loadOfflineQueue();
        queue.push({
            qr_data: qrData,
            created_at: new Date().toISOString()
        });
        saveOfflineQueue(queue);
    }

    async function syncOfflineQueue() {
        if (!navigator.onLine) return;

        let queue = loadOfflineQueue();
        if (!queue.length) return;

        const remaining = [];

        for (const item of queue) {
            try {
                const response = await fetch(ATTENDANCE_SCAN_URL, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        qr_data: item.qr_data,
                        from_queue: true
                    })
                });

                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    throw new Error('Unexpected response format');
                }
                const data = await response.json();

                if (!data.success) {
                    console.warn('Queued scan failed on sync');
                }
            } catch (e) {
                console.error('Error syncing queued scan', e);
                remaining.push(item);
                break; // stop on first network error
            }
        }

        saveOfflineQueue(remaining);

        if (queue.length !== remaining.length) {
            showMessage('<strong>Offline scans synced.</strong>', 'success');
        }
    }

    // Start Scanner
    startButton.addEventListener('click', function() {
        startScanner();
    });

    // Stop Scanner
    stopButton.addEventListener('click', function() {
        stopScanner();
    });

    function updateNetworkStatus() {
        if (!navigator.onLine) {
            statusDiv.textContent = 'You appear to be offline. Scans will be saved and synced when you are back online.';
            statusDiv.className = 'text-center text-sm text-red-600 font-semibold';
        } else {
            statusDiv.textContent = 'You are online. Scans will be submitted immediately.';
            statusDiv.className = 'text-center text-sm text-green-600 font-semibold';
        }
    }

    window.addEventListener('online', () => {
        updateNetworkStatus();
        syncOfflineQueue();
    });
    window.addEventListener('offline', updateNetworkStatus);

    /**
     * Browsers require a secure context for getUserMedia. Some mobile WebViews set
     * isSecureContext incorrectly on https:// LAN URLs; trust protocol + hostname too.
     */
    function hasSecureContextForCamera() {
        if (location.protocol === 'https:') {
            return true;
        }
        if (typeof window.isSecureContext !== 'undefined' && window.isSecureContext) {
            return true;
        }
        const h = location.hostname;
        return h === 'localhost' || h === '127.0.0.1' || h === '[::1]';
    }

    function getFriendlyCameraError(err) {
        const msg = String((err && (err.message || err.name)) || '').toLowerCase();

        if (!hasSecureContextForCamera()) {
            return 'The camera only works on HTTPS or on localhost — not on plain http:// with your Wi‑Fi IP. Use the “Open scanner on HTTPS” button above, or open https://YOUR_CLASSROOM_PC:9443 (Caddy) on the same Wi‑Fi.';
        }
        if (msg.includes('notallowed') || msg.includes('permission') || msg.includes('denied')) {
            return 'Camera permission is blocked. Click the lock/camera icon in your browser address bar, allow camera access, then reload this page.';
        }
        if (msg.includes('notfound') || msg.includes('no camera') || msg.includes('overconstrained')) {
            return 'No usable camera was found for this device. Connect a camera (or enable one), then try again.';
        }
        if (msg.includes('notreadable') || msg.includes('trackstart') || msg.includes('inuse')) {
            return 'Your camera is currently in use by another app or tab. Close other camera apps and try again.';
        }

        return 'Unable to access camera right now. Please check camera permission and try again.';
    }

    async function startScanner() {
        if (isScanning) return;

        statusDiv.textContent = 'Initializing camera...';
        statusDiv.className = 'text-center text-sm text-gray-600';
        resultDiv.classList.add('hidden');

        html5QrCode = new Html5Qrcode("qr-reader");

        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };

        try {
            // Try back camera first (best for phones)
            await html5QrCode.start(
                { facingMode: "environment" },
                config,
                onScanSuccess,
                onScanError
            );
        } catch (firstErr) {
            try {
                // Fallback for laptops/browsers that don't support facingMode well
                const cameras = await Html5Qrcode.getCameras();
                if (!cameras || !cameras.length) {
                    throw firstErr;
                }

                await html5QrCode.start(
                    { deviceId: { exact: cameras[0].id } },
                    config,
                    onScanSuccess,
                    onScanError
                );
            } catch (fallbackErr) {
                const userMessage = getFriendlyCameraError(fallbackErr);
                statusDiv.textContent = userMessage;
                statusDiv.className = 'text-center text-sm text-red-600';
                console.error('Error starting scanner:', fallbackErr);
                return;
            }
        }

        isScanning = true;
        startButton.classList.add('hidden');
        stopButton.classList.remove('hidden');
        updateNetworkStatus();
    }

    function stopScanner() {
        if (!isScanning || !html5QrCode) return;

        html5QrCode.stop().then(() => {
            isScanning = false;
            startButton.classList.remove('hidden');
            stopButton.classList.add('hidden');
            statusDiv.textContent = '';
        }).catch(err => {
            console.error('Error stopping scanner:', err);
        });
    }

    function onScanSuccess(decodedText, decodedResult) {
        const now = Date.now();
        if (scanInFlight) {
            return;
        }
        if (decodedText === lastSubmittedPayload && (now - lastSubmittedAt) < SCAN_COOLDOWN_MS) {
            return;
        }

        scanInFlight = true;
        lastSubmittedPayload = decodedText;
        lastSubmittedAt = now;

        // Stop scanner immediately after a read (stop is async; guard above prevents duplicate POSTs)
        stopScanner();

        lastDecodedText = decodedText;

        // If offline, queue the scan locally for later sync
        if (!navigator.onLine) {
            scanInFlight = false;
            enqueueScan(decodedText);
            showMessage(
                '<strong>Saved offline.</strong><br>Your attendance scan has been stored and will be submitted automatically when you are back online.',
                'info'
            );
            return;
        }

        // Show processing message
        showMessage('Processing...', 'info');

        const controller = new AbortController();
        const timeoutMs = 45000;
        const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

        fetch(ATTENDANCE_SCAN_URL, {
            method: 'POST',
            signal: controller.signal,
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                qr_data: decodedText
            })
        })
        .then(async (response) => {
            const text = await response.text();
            let data = null;
            try {
                data = text ? JSON.parse(text) : null;
            } catch (e) {
                data = null;
            }

            if (response.status === 419) {
                showMessage('<strong>Session expired</strong><br>Reload this page, log in again, then scan.', 'error');
                return;
            }
            if (response.status === 401) {
                showMessage('<strong>Not signed in</strong><br>Open this page again and log in, then scan.', 'error');
                return;
            }

            if (!data || typeof data !== 'object') {
                showMessage(
                    '<strong>Server error</strong><br>The server did not return JSON. If you are not on the class HTTPS link, open the app using the HTTPS address shown at the top of this page.',
                    'error'
                );
                return;
            }

            if (data.success) {
                const msg = escapeHtml(data.message || 'Your attendance was recorded.');
                const course = escapeHtml(data.course || '');
                const section = escapeHtml(data.section || '');
                const time = escapeHtml(data.marked_at || '');
                showMessage(
                    '<strong>Success</strong><br>' + msg + '<br>' +
                    '<span class="text-sm">Course: ' + course + '<br>' +
                    'Section: ' + section + '<br>' +
                    'Time: ' + time + '</span>',
                    'success'
                );

                syncOfflineQueue();

                setTimeout(() => {
                    location.reload();
                }, 3000);
                return;
            }

            const userMsg = (typeof data.message === 'string' && data.message.trim())
                ? data.message
                : 'We could not record your attendance. Please try again.';
            showMessage('<strong>Something went wrong</strong><br>' + escapeHtml(userMsg), 'error');
        })
        .catch((err) => {
            if (err && err.name === 'AbortError') {
                showMessage('<strong>Request timed out</strong><br>Check Wi‑Fi, reload the page, and try again.', 'error');
            } else {
                const networkMessage = navigator.onLine
                    ? 'We could not reach the server. Use the HTTPS class link (top of page), check Wi‑Fi, and try again.'
                    : 'You appear to be offline. When you are back online, your scan can be submitted automatically if it was saved.';
                showMessage('<strong>Connection problem</strong><br>' + escapeHtml(networkMessage), 'error');

                if (!navigator.onLine) {
                    enqueueScan(decodedText);
                }
            }
        })
        .finally(() => {
            clearTimeout(timeoutId);
            scanInFlight = false;
        });
    }

    function onScanError(errorMessage) {
        // Ignore scan errors (they occur frequently during scanning)
    }

    function showMessage(message, type) {
        const colors = {
            success: 'bg-green-100 border-green-400 text-green-700',
            error: 'bg-red-100 border-red-400 text-red-700',
            info: 'bg-blue-100 border-blue-400 text-blue-700'
        };

        resultDiv.innerHTML = `
            <div class="border ${colors[type]} px-4 py-3 rounded">
                ${message}
            </div>
        `;
        resultDiv.classList.remove('hidden');
    }

    // Initialize network status on load and attempt to sync any queued scans
    updateNetworkStatus();
    if (navigator.onLine) {
        syncOfflineQueue();
    }
</script>
@endpush
@endsection