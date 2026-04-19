@extends('layouts.app')

@section('title', 'Your class timing')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-slate-100">Your class timing</h1>
        <p class="text-gray-600 dark:text-slate-300 mt-2">These choices only affect classes you teach. If you leave a box empty, the school-wide setting is used.</p>
    </div>

    <div class="bg-white dark:bg-slate-800/90 rounded-lg shadow p-6 border border-transparent dark:border-slate-600/80">
        <form method="POST" action="{{ route('faculty.settings.class-rules.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label for="check_in_code_valid_minutes" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">How long a check-in stays open (minutes)</label>
                <p class="text-sm text-gray-500 dark:text-slate-400 mb-2">After you start attendance, students can use the code for about this many minutes. School default: {{ $schoolCheckMinutes }}.</p>
                <input
                    type="number"
                    name="check_in_code_valid_minutes"
                    id="check_in_code_valid_minutes"
                    value="{{ old('check_in_code_valid_minutes', $checkMinutes) }}"
                    min="1"
                    max="120"
                    placeholder="{{ $schoolCheckMinutes }}"
                    class="w-full max-w-xs px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100"
                >
            </div>

            <div>
                <label for="late_after_minutes" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Minutes after start still counted as “on time”</label>
                <p class="text-sm text-gray-500 dark:text-slate-400 mb-2">Arrivals within this many minutes after class begins are marked on time; after that they are late. School default: {{ $schoolLateMinutes }}.</p>
                <input
                    type="number"
                    name="late_after_minutes"
                    id="late_after_minutes"
                    value="{{ old('late_after_minutes', $lateMinutes) }}"
                    min="0"
                    max="120"
                    placeholder="{{ $schoolLateMinutes }}"
                    class="w-full max-w-xs px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100"
                >
            </div>

            <div>
                <label for="absent_after_minutes" class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Minutes after start before “absent” is counted</label>
                <p class="text-sm text-gray-500 dark:text-slate-400 mb-2">Until this many minutes have passed after attendance starts, students who have not checked in are not counted as absent yet. School default: {{ $schoolAbsentMinutes }}.</p>
                <input
                    type="number"
                    name="absent_after_minutes"
                    id="absent_after_minutes"
                    value="{{ old('absent_after_minutes', $absentMinutes) }}"
                    min="0"
                    max="240"
                    placeholder="{{ $schoolAbsentMinutes }}"
                    class="w-full max-w-xs px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100"
                >
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold">
                    Save
                </button>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-6 py-3 rounded-lg border border-gray-300 dark:border-slate-500 text-gray-700 dark:text-slate-200 font-semibold hover:bg-gray-50 dark:hover:bg-slate-700">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
