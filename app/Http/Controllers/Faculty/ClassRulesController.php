<?php

namespace App\Http\Controllers\Faculty;

use App\Http\Controllers\Controller;
use App\Support\AttendanceConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClassRulesController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        abort_unless($user->isFaculty(), 403);

        return view('faculty.settings.class-rules', [
            'checkMinutes' => $user->check_in_code_valid_minutes,
            'lateMinutes' => $user->late_after_minutes,
            'absentMinutes' => $user->absent_after_minutes,
            'schoolCheckMinutes' => (int) AttendanceConfig::get('qr_expiration_minutes', 10),
            'schoolLateMinutes' => (int) AttendanceConfig::get('late_threshold_minutes', 15),
            'schoolAbsentMinutes' => (int) AttendanceConfig::get('absent_after_minutes', 30),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        abort_unless($user->isFaculty(), 403);

        $data = $request->validate([
            'check_in_code_valid_minutes' => ['nullable', 'integer', 'min:1', 'max:120'],
            'late_after_minutes' => ['nullable', 'integer', 'min:0', 'max:120'],
            'absent_after_minutes' => ['nullable', 'integer', 'min:0', 'max:240'],
        ]);

        $user->update([
            'check_in_code_valid_minutes' => $request->filled('check_in_code_valid_minutes')
                ? (int) $data['check_in_code_valid_minutes']
                : null,
            'late_after_minutes' => $request->filled('late_after_minutes')
                ? (int) $data['late_after_minutes']
                : null,
            'absent_after_minutes' => $request->filled('absent_after_minutes')
                ? (int) $data['absent_after_minutes']
                : null,
        ]);

        return redirect()->route('faculty.settings.class-rules.edit')
            ->with('success', 'Your class rules were saved. Leave a field empty to use the school default for that item.');
    }
}
