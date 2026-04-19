<?php

namespace App\Support;

use App\Models\AttendanceSetting;

class AttendanceConfig
{
    public static function get(string $key, $default = null)
    {
        // Allow overrides from DB; fall back to config/attendance.php
        $dbValue = AttendanceSetting::get($key);

        if (! is_null($dbValue)) {
            // Cast booleans and integers where appropriate
            if (in_array($key, ['require_network_match', 'auto_close_sessions'], true)) {
                return filter_var($dbValue, FILTER_VALIDATE_BOOL);
            }

            if (in_array($key, ['qr_expiration_minutes', 'late_threshold_minutes', 'rate_limit_scans_per_minute', 'absent_after_minutes'], true)) {
                return (int) $dbValue;
            }

            return $dbValue;
        }

        // Fallback to config file
        $configKey = "attendance.{$key}";

        return config($configKey, $default);
    }
}
