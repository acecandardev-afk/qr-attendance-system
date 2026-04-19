<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Schedule;
use App\Support\AttendanceConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AttendanceSessionService
{
    /**
     * Start a new attendance session for a schedule
     */
    public function startSession(Schedule $schedule, int $facultyId): AttendanceSession
    {
        // Check if there's already an active session for this schedule
        $existingSession = AttendanceSession::where('schedule_id', $schedule->id)
            ->where('status', 'active')
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if ($existingSession) {
            throw new \Exception('FACULTY_SESSION_ALREADY_ACTIVE');
        }

        // Generate unique session token
        $sessionToken = $this->generateUniqueToken();

        $schedule->loadMissing('faculty');

        $startedAt = Carbon::now();
        $qrMinutes = $schedule->faculty?->check_in_code_valid_minutes;
        if ($qrMinutes === null || $qrMinutes < 1) {
            $qrMinutes = (int) AttendanceConfig::get('qr_expiration_minutes', 10);
        }
        $expiresAt = $startedAt->copy()->addMinutes($qrMinutes);

        // Create session record
        $session = AttendanceSession::create([
            'session_token' => $sessionToken,
            'schedule_id' => $schedule->id,
            'faculty_id' => $facultyId,
            'started_at' => $startedAt,
            'expires_at' => $expiresAt,
            'status' => 'active',
        ]);

        // Automatically generate and save QR code when session starts
        try {
            $qrCodePath = $this->generateQrCode($session);
            $session->update(['qr_code_path' => $qrCodePath]);
        } catch (\Throwable $e) {
            // Session is still valid; show page will generate QR inline if path is missing
            report($e);
        }

        return $session->fresh();
    }

    /**
     * Generate unique session token with HMAC signature
     */
    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(64);
        } while (AttendanceSession::where('session_token', $token)->exists());

        return $token;
    }

    /**
     * Generate QR code for the session and save to storage.
     * Uses SVG format (no Imagick required); same payload = 100% accurate scanning.
     */
    private function generateQrCode(AttendanceSession $session): string
    {
        $payload = $this->createQrPayload($session);
        $qrCode = QrCode::format('svg')
            ->size(400)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($payload);
        $qrCode = $qrCode instanceof HtmlString ? $qrCode->toHtml() : (string) $qrCode;

        $dir = config('attendance.qr_storage_path', 'qrcodes');
        $filename = "qr_session_{$session->id}_".time().'.svg';
        $path = $dir.'/'.$filename;

        if (! Storage::disk('public')->exists($dir)) {
            Storage::disk('public')->makeDirectory($dir);
        }
        Storage::disk('public')->put($path, $qrCode);

        return $path;
    }

    /**
     * Get QR code as a data URL (for inline display when file is missing).
     * SVG avoids Imagick dependency and encodes the same payload for accurate scanning.
     */
    public function getQrCodeDataUrl(AttendanceSession $session): string
    {
        $payload = $this->createQrPayload($session);
        $svg = QrCode::format('svg')
            ->size(400)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($payload);
        $svg = $svg instanceof HtmlString ? $svg->toHtml() : (string) $svg;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Create QR code payload with HMAC signature
     */
    private function createQrPayload(AttendanceSession $session): string
    {
        $data = [
            'session_id' => $session->id,
            'token' => $session->session_token,
            'timestamp' => $session->started_at->timestamp,
        ];

        // Generate HMAC signature
        $signature = hash_hmac('sha256', json_encode($data), config('app.key'));

        $data['signature'] = $signature;

        return json_encode($data);
    }

    /**
     * Verify QR code payload signature
     */
    public function verifyQrPayload(string $payload): array
    {
        $data = json_decode($payload, true);

        if (! $data || ! isset($data['signature'])) {
            throw new \Exception('This is not a valid attendance code. Please scan the QR code your instructor shows in class.');
        }

        // Extract signature
        $signature = $data['signature'];
        unset($data['signature']);

        // Verify signature
        $expectedSignature = hash_hmac('sha256', json_encode($data), config('app.key'));

        if (! hash_equals($expectedSignature, $signature)) {
            throw new \Exception('This attendance code could not be verified. Ask your instructor for a fresh code.');
        }

        return $data;
    }

    /**
     * Close an attendance session manually
     */
    public function closeSession(AttendanceSession $session): void
    {
        $closedAt = Carbon::now();
        $session->update([
            'status' => 'closed',
            'closed_at' => $closedAt,
        ]);
        $this->finalizeMissingAbsentRecords($session->fresh());
    }

    /**
     * Create absent rows for enrolled students who never checked in (idempotent).
     */
    public function finalizeMissingAbsentRecords(AttendanceSession $session): void
    {
        $session->loadMissing('schedule');
        if (! $session->schedule) {
            return;
        }

        $stats = app(AttendanceSessionStatisticsService::class);
        $studentIds = $stats->enrolledStudentIds($session);
        if ($studentIds->isEmpty()) {
            return;
        }

        $existing = $session->attendanceRecords()->pluck('student_id')->unique();
        $missing = $studentIds->diff($existing);
        $markedAt = $session->closed_at ?? $session->expires_at ?? Carbon::now();

        foreach ($missing as $studentId) {
            AttendanceRecord::firstOrCreate(
                [
                    'attendance_session_id' => $session->id,
                    'student_id' => $studentId,
                ],
                [
                    'status' => 'absent',
                    'marked_at' => $markedAt,
                    'ip_address' => null,
                    'network_identifier' => null,
                ]
            );
        }
    }

    /**
     * Mark expired sessions
     */
    public function markExpiredSessions(): int
    {
        $sessions = AttendanceSession::where('status', 'active')
            ->where('expires_at', '<=', Carbon::now())
            ->get();

        $now = Carbon::now();
        foreach ($sessions as $session) {
            $session->update([
                'status' => 'expired',
                'closed_at' => $session->closed_at ?? $now,
            ]);
            $this->finalizeMissingAbsentRecords($session->fresh());
        }

        return $sessions->count();
    }

    /**
     * Get network identifier from IP address
     */
    public function getNetworkIdentifier(string $ipAddress): string
    {
        // Extract subnet (first 3 octets for IPv4)
        $parts = explode('.', $ipAddress);

        if (count($parts) === 4) {
            return implode('.', array_slice($parts, 0, 3)).'.0/24';
        }

        return $ipAddress; // Return as-is if not IPv4
    }

    /**
     * Check if IP belongs to allowed network
     */
    public function isNetworkAllowed(string $ipAddress, ?string $allowedNetwork): bool
    {
        if (empty($allowedNetwork)) {
            return true; // No restriction
        }

        $studentNetwork = $this->getNetworkIdentifier($ipAddress);

        return $studentNetwork === $allowedNetwork;
    }
}
