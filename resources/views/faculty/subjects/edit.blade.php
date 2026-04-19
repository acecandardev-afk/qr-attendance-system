@extends('layouts.app')

@section('title', 'Edit subject')

@php
    $course = $schedule->course;
    $section = $schedule->section;
    $facultyUser = auth()->user()->loadMissing('department');
    $startDefault = old('start_time', $schedule->start_time?->format('H:i'));
    $endDefault = old('end_time', $schedule->end_time?->format('H:i'));
@endphp

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 pb-10">
    <div class="mb-6">
        <a href="{{ route('faculty.sessions.index') }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">← Back to My Subjects</a>
        <h1 class="text-3xl font-bold text-gray-800 dark:text-slate-100 mt-4">Edit subject</h1>
        <p class="text-gray-600 dark:text-slate-400 mt-2 text-sm">Update the subject details and class schedule. The section must stay the same (you can fix spelling or spacing if it still matches this class).</p>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 border border-slate-200 dark:border-slate-600">
        <form action="{{ route('faculty.subjects.update', $schedule) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            @if($subjectCreateDepartments->isNotEmpty())
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Department *</label>
                    <select name="department_id" required class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('department_id') border-red-500 @enderror">
                        <option value="" @selected(old('department_id') === null || old('department_id') === '')>—</option>
                        @foreach($subjectCreateDepartments as $dept)
                            <option value="{{ $dept->id }}" {{ (string) old('department_id', $course->department_id) === (string) $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            @elseif($facultyUser->department_id)
                <p class="text-sm text-slate-600 dark:text-slate-400">
                    Department: <strong>{{ $facultyUser->department?->name ?? '—' }}</strong>
                </p>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Subject code *</label>
                    <input type="text" name="code" value="{{ old('code', $course->code) }}" required maxlength="255" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('code') border-red-500 @enderror">
                    @error('code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Subject name *</label>
                    <input type="text" name="name" value="{{ old('name', $course->name) }}" required maxlength="255" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Description</label>
                    <textarea name="description" rows="2" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('description') border-red-500 @enderror">{{ old('description', $course->description) }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Units *</label>
                    <input type="number" name="units" value="{{ old('units', $course->units) }}" required min="0" step="1" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('units') border-red-500 @enderror">
                    @error('units')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Section *</label>
                    <input type="text" name="section_name" value="{{ old('section_name', $section?->name) }}" required maxlength="255" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('section_name') border-red-500 @enderror">
                    @error('section_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Class days *</label>
                    <select name="day_of_week" required class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('day_of_week') border-red-500 @enderror">
                        <option value="" @selected(old('day_of_week') === null || old('day_of_week') === '')>—</option>
                        @foreach(\App\Models\Schedule::DAY_PATTERNS as $pat)
                            <option value="{{ $pat }}" {{ old('day_of_week', $schedule->day_of_week) === $pat ? 'selected' : '' }}>
                                @switch($pat)
                                    @case('MWF') Mon / Wed / Fri @break
                                    @case('TTH') Tue / Thu @break
                                    @case('SAT') Saturday @break
                                    @case('SUN') Sunday @break
                                    @default {{ $pat }}
                                @endswitch
                            </option>
                        @endforeach
                    </select>
                    @error('day_of_week')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid grid-cols-2 gap-3 md:col-span-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Start *</label>
                        <input type="time" name="start_time" value="{{ $startDefault }}" required class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('start_time') border-red-500 @enderror">
                        @error('start_time')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">End *</label>
                        <input type="time" name="end_time" value="{{ $endDefault }}" required class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('end_time') border-red-500 @enderror">
                        @error('end_time')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 justify-end pt-2">
                <a href="{{ route('faculty.sessions.index') }}" class="inline-flex items-center px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-500 text-slate-800 dark:text-slate-200 font-medium text-sm hover:bg-slate-50 dark:hover:bg-slate-700">
                    Cancel
                </a>
                <button type="submit" class="inline-flex items-center bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg font-semibold text-sm">
                    Save changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
