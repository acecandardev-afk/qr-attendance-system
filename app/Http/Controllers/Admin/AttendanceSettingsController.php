<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSetting;
use App\Support\AttendanceConfig;
use Illuminate\Http\Request;

class AttendanceSettingsController extends Controller
{
    public function edit()
    {
        $settings = [
            'qr_expiration_minutes' => AttendanceConfig::get('qr_expiration_minutes', 10),
            'late_threshold_minutes' => AttendanceConfig::get('late_threshold_minutes', 15),
            'rate_limit_scans_per_minute' => AttendanceConfig::get('rate_limit_scans_per_minute', 30),
            'auto_close_sessions' => AttendanceConfig::get('auto_close_sessions', false),
        ];

        return view('admin.settings.attendance', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'qr_expiration_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'late_threshold_minutes' => ['required', 'integer', 'min:0', 'max:60'],
            'rate_limit_scans_per_minute' => ['required', 'integer', 'min:1', 'max:200'],
            'auto_close_sessions' => ['nullable', 'boolean'],
        ]);

        AttendanceSetting::set('qr_expiration_minutes', $data['qr_expiration_minutes']);
        AttendanceSetting::set('late_threshold_minutes', $data['late_threshold_minutes']);
        AttendanceSetting::set('rate_limit_scans_per_minute', $data['rate_limit_scans_per_minute']);
        AttendanceSetting::set('require_network_match', false);
        AttendanceSetting::set('auto_close_sessions', $request->boolean('auto_close_sessions'));

        return redirect()
            ->route('admin.settings.attendance.edit')
            ->with('success', 'Attendance settings updated successfully.');
    }
}
