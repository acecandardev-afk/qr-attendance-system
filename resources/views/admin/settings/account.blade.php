@extends('layouts.app')

@section('title', 'Account settings')

@section('content')
<div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Settings</h1>
        <p class="text-gray-600 mt-2">Change your administrator password.</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6 space-y-6">
        <form method="POST" action="{{ route('admin.settings.account.password') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current password *</label>
                <input type="password" name="current_password" id="current_password" required autocomplete="current-password"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('current_password') border-red-500 @enderror">
                @error('current_password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">New password *</label>
                <input type="password" name="password" id="password" required autocomplete="new-password"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('password') border-red-500 @enderror">
                @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm new password *</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg">Update password</button>
        </form>

        <div class="border-t border-gray-200 pt-6">
            <p class="text-sm text-gray-600 mb-2">Attendance rules and grace periods</p>
            <a href="{{ route('admin.settings.attendance.edit') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Open attendance settings →</a>
        </div>
    </div>
</div>
@endsection
