<?php

namespace App\Services;

use App\Models\AttendanceAttempt;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\AttendanceConfig;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Throwable;

class AttendanceMarkingService
{
    protected $sessionService;

    public function __construct(AttendanceSessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Mark student attendance by scanning QR code
     */
    public function markAttendance(string $qrPayload, User $student, string $ipAddress, string $userAgent): array
    {
        DB::beginTransaction();

        try {
            // Step 1: Verify QR code signature
            $qrData = $this->sessionService->verifyQrPayload($qrPayload);

            // Step 1.1: Reject stale signed payloads (helps protect replay from delayed/offline queue sync)
            $payloadTimestamp = isset($qrData['timestamp']) ? Carbon::createFromTimestamp((int) $qrData['timestamp']) : null;
            $maxPayloadAge = (int) AttendanceConfig::get('qr_expiration_minutes', 10) + 5; // grace window
            if (! $payloadTimestamp || $payloadTimestamp->lt(Carbon::now()->subMinutes($maxPayloadAge))) {
                throw new \Exception('This attendance code is too old. Ask your instructor to show the current QR code.');
            }

            // Step 2: Get session
            $session = AttendanceSession::with([
                'schedule' => fn ($q) => $q->withTrashed(),
                'schedule.section' => fn ($q) => $q->withTrashed(),
                'schedule.course' => fn ($q) => $q->withTrashed(),
            ])
                ->find($qrData['session_id'] ?? null);

            if (! $session && ! empty($qrData['token'])) {
                $session = AttendanceSession::with([
                    'schedule' => fn ($q) => $q->withTrashed(),
                    'schedule.section' => fn ($q) => $q->withTrashed(),
                    'schedule.course' => fn ($q) => $q->withTrashed(),
                ])
                    ->where('session_token', $qrData['token'])
                    ->first();
            }

            if (! $session) {
                throw new ModelNotFoundException('Attendance session not found');
            }

            if (! $session->schedule) {
                throw new \Exception('This attendance code is no longer available. Ask your instructor to show the current QR code.');
            }

            // Step 3: Validate session token matches
            if ($session->session_token !== $qrData['token']) {
                $this->logAttempt($session->id, $student->id, $qrData['token'], 'invalid_token',
                    $ipAddress, $userAgent, 'Session token mismatch');
                throw new \Exception('This attendance code does not match the active session. Scan the QR code your instructor is displaying now.');
            }

            // Step 4: Check rate limiting
            $this->checkRateLimit($student->id, $ipAddress, $userAgent);

            // Step 5: Validate session is active
            if ($session->status !== 'active') {
                $this->logAttempt($session->id, $student->id, $session->session_token, 'expired',
                    $ipAddress, $userAgent, "Session status: {$session->status}");
                throw new \Exception('This check-in is already closed. If you are in class, ask your instructor to start a new attendance session.');
            }

            // Step 6: Check if session has expired
            if ($session->isExpired()) {
                $this->logAttempt($session->id, $student->id, $session->session_token, 'expired',
                    $ipAddress, $userAgent, 'Session has expired');
                throw new \Exception('This QR code has expired. Ask your instructor to refresh the attendance code.');
            }

            // Step 7: Verify student is enrolled for this class schedule (or whole section if no schedules linked)
            $isEnrolled = Enrollment::query()
                ->where('student_id', $student->id)
                ->eligibleForSchedule($session->schedule)
                ->exists();

            if (! $isEnrolled) {
                $this->logAttempt($session->id, $student->id, $session->session_token, 'not_enrolled',
                    $ipAddress, $userAgent, 'Student not enrolled in this section');
                throw new \Exception('You are not enrolled in this class, so attendance cannot be recorded.');
            }

            // Step 8: Determine attendance status (present or late)
            $attendanceStatus = $this->determineAttendanceStatus($session);

            // Step 9: Create attendance record if it doesn't exist.
            // If the student scans multiple times for the same session, keep only one record.
            $record = AttendanceRecord::firstOrCreate(
                [
                    'attendance_session_id' => $session->id,
                    'student_id' => $student->id,
                ],
                [
                    'status' => $attendanceStatus,
                    'marked_at' => Carbon::now(),
                    'ip_address' => $ipAddress,
                    'network_identifier' => null,
                ]
            );

            if (! $record->wasRecentlyCreated) {
                $this->logAttempt($session->id, $student->id, $session->session_token, 'duplicate',
                    $ipAddress, $userAgent, 'Attendance already marked');

                DB::commit();

                return [
                    'success' => true,
                    'status' => $record->status,
                    'message' => 'Your attendance for this class is already recorded.',
                    'record' => $record,
                    'session' => $session,
                ];
            }

            // Step 10: Log successful attempt
            $this->logAttempt($session->id, $student->id, $session->session_token, 'success',
                $ipAddress, $userAgent, null);

            DB::commit();

            return [
                'success' => true,
                'status' => $attendanceStatus,
                'message' => $this->getSuccessMessage($attendanceStatus),
                'record' => $record,
                'session' => $session,
            ];

        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'This attendance code is no longer available. Ask your instructor to show the current QR code.',
            ];
        } catch (Throwable $e) {
            DB::rollBack();

            $userSafeMessages = [
                'This is not a valid attendance code. Please scan the QR code your instructor shows in class.',
                'This attendance code could not be verified. Ask your instructor for a fresh code.',
                'This attendance code is too old. Ask your instructor to show the current QR code.',
                'This attendance code does not match the active session. Scan the QR code your instructor is displaying now.',
                'This check-in is already closed. If you are in class, ask your instructor to start a new attendance session.',
                'This QR code has expired. Ask your instructor to refresh the attendance code.',
                'You are not enrolled in this class, so attendance cannot be recorded.',
                'Your attendance for this class is already recorded.',
                'This attendance code is no longer available. Ask your instructor to show the current QR code.',
                'Connect to the classroom network, then try again. If you need help, ask your instructor.',
                'Too many scan attempts. Please wait a moment and try again.',
            ];

            $message = in_array($e->getMessage(), $userSafeMessages, true)
                ? $e->getMessage()
                : 'We could not record your attendance. Please try again in a moment.';

            return [
                'success' => false,
                'message' => $message,
            ];
        }
    }

    /**
     * Check rate limiting for student
     */
    protected function checkRateLimit(int $studentId, string $ipAddress, string $userAgent): void
    {
        $rateLimit = (int) AttendanceConfig::get('rate_limit_scans_per_minute', 30);
        $timeWindow = 1; // minute

        $recentAttempts = AttendanceAttempt::where('student_id', $studentId)
            ->where('created_at', '>=', Carbon::now()->subMinutes($timeWindow))
            ->count();

        if ($recentAttempts >= $rateLimit) {
            $this->logAttempt(null, $studentId, null, 'rate_limited',
                $ipAddress, $userAgent, 'Too many scan attempts');
            throw new \Exception('Too many scan attempts. Please wait a moment and try again.');
        }
    }

    /**
     * Determine if student is present or late
     */
    protected function determineAttendanceStatus(AttendanceSession $session): string
    {
        $schedule = $session->schedule;
        $lateThreshold = (int) AttendanceConfig::get('late_threshold_minutes', 15);

        // Get schedule start time for today
        $scheduleStart = Carbon::parse($schedule->start_time);
        $now = Carbon::now();

        // Calculate minutes after schedule start
        $minutesAfterStart = $now->diffInMinutes($scheduleStart, false);

        // If negative, student is early/on-time
        if ($minutesAfterStart <= 0) {
            return 'present';
        }

        // If within threshold, still present
        if ($minutesAfterStart <= $lateThreshold) {
            return 'present';
        }

        // Otherwise, mark as late
        return 'late';
    }

    /**
     * Log attendance attempt
     */
    protected function logAttempt(
        ?int $sessionId,
        ?int $studentId,
        ?string $token,
        string $result,
        string $ipAddress,
        string $userAgent,
        ?string $errorMessage
    ): void {
        AttendanceAttempt::create([
            'attendance_session_id' => $sessionId,
            'student_id' => $studentId,
            'session_token' => $token,
            'result' => $result,
            'ip_address' => $ipAddress,
            'network_identifier' => null,
            'error_message' => $errorMessage,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Get success message based on status
     */
    protected function getSuccessMessage(string $status): string
    {
        return match ($status) {
            'present' => 'Your attendance is recorded. Thank you.',
            'late' => 'Your attendance is recorded as late. Try to arrive on time next class.',
            default => 'Your attendance has been saved.',
        };
    }

    /**
     * Get student attendance summary
     */
    public function getStudentSummary(User $student): array
    {
        $records = $student->attendanceRecords()
            ->with(['attendanceSession.schedule.course', 'attendanceSession.schedule.section'])
            ->orderByDesc('marked_at')
            ->get();

        $uniqueRecords = $records
            ->unique('attendance_session_id')
            ->values()
            ->filter(function ($record) {
                $session = $record->attendanceSession;
                if (!$session) {
                    return false;
                }

                $schedule = $session->schedule;
                if (!$schedule) {
                    return false;
                }

                if (!$schedule->course || !$schedule->section) {
                    return false;
                }

                return true;
            })
            ->values();

        return [
            'total' => $uniqueRecords->count(),
            'present' => $uniqueRecords->where('status', 'present')->count(),
            'late' => $uniqueRecords->where('status', 'late')->count(),
            'absent' => $uniqueRecords->where('status', 'absent')->count(),
            'excused' => $uniqueRecords->where('status', 'excused')->count(),
            'recent_records' => $uniqueRecords->take(10),
        ];
    }
}
