@extends('layouts.app')

@section('title', 'Attendance Settings')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Attendance Settings</h1>
        <p class="text-gray-600 mt-2 text-sm">
            Choose how long QR codes work, when a student is considered late, and other attendance options.
        </p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="{{ route('admin.settings.attendance.update') }}" class="space-y-6">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    QR code time limit (minutes)
                </label>
                <input
                    type="number"
                    name="qr_expiration_minutes"
                    min="1"
                    max="120"
                    value="{{ old('qr_expiration_minutes', $settings['qr_expiration_minutes']) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <p class="text-xs text-gray-500 mt-1">
                    After you start attendance, the QR code will stop working after this many minutes.
                </p>
                @error('qr_expiration_minutes')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Late after (minutes)
                </label>
                <input
                    type="number"
                    name="late_threshold_minutes"
                    min="0"
                    max="60"
                    value="{{ old('late_threshold_minutes', $settings['late_threshold_minutes']) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <p class="text-xs text-gray-500 mt-1">
                    Students will be marked "Late" if they scan after this many minutes from the class start time.
                </p>
                @error('late_threshold_minutes')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Count as absent after (minutes from session start)
                </label>
                <input
                    type="number"
                    name="absent_after_minutes"
                    min="0"
                    max="240"
                    value="{{ old('absent_after_minutes', $settings['absent_after_minutes']) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <p class="text-xs text-gray-500 mt-1">
                    After class attendance starts, this many minutes must pass before students who have not checked in are counted as absent (faculty can override in class timing).
                </p>
                @error('absent_after_minutes')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Limit repeated scans (per minute)
                </label>
                <input
                    type="number"
                    name="rate_limit_scans_per_minute"
                    min="1"
                    max="200"
                    value="{{ old('rate_limit_scans_per_minute', $settings['rate_limit_scans_per_minute']) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <p class="text-xs text-gray-500 mt-1">
                    If a student scans too many times in one minute, extra scans will be blocked for a short time.
                </p>
                @error('rate_limit_scans_per_minute')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center">
                <input
                    id="auto_close_sessions"
                    type="checkbox"
                    name="auto_close_sessions"
                    value="1"
                    @checked(old('auto_close_sessions', $settings['auto_close_sessions']) == true)
                    class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                >
                <label for="auto_close_sessions" class="ml-2 block text-sm text-gray-700">
                    Automatically close attendance after it ends
                </label>
            </div>
            <p class="text-xs text-gray-500 -mt-2 mb-4 ml-6">
                When enabled, the system will close the attendance session automatically when its time is up.
            </p>

            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

