@extends('layouts.app')

@section('title', 'Change password')

@section('content')
<div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-slate-100">Change password</h1>
        <p class="text-gray-600 dark:text-slate-300 mt-2 text-sm">Choose a strong password you have not used elsewhere.</p>
    </div>

    <div class="bg-white dark:bg-slate-800/90 rounded-lg shadow p-6 border border-transparent dark:border-slate-600/80">
        <form method="POST" action="{{ route('settings.password.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Current password</label>
                <input type="password" name="current_password" id="current_password" required autocomplete="current-password"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('current_password') border-red-500 @enderror">
                @error('current_password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">New password</label>
                <input type="password" name="password" id="password" required autocomplete="new-password"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('password') border-red-500 @enderror">
                @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-2">Confirm new password</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                       class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg">Save new password</button>
        </form>
    </div>
</div>
@endsection
