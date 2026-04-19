@extends('layouts.app')

@section('title', 'My Subjects')

@php
    $scheduleBaseUrl = url('/faculty/schedules');
@endphp

@section('content')
<div
    class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
    x-data="facultySubjectEnroll({
        base: @js($scheduleBaseUrl),
        todayDataUrl: @js(route('faculty.sessions.today-data')),
        todaySchedules: @js($todaySchedulesPayload),
        todayDateLabel: @js($todayDateLabel),
        csrfToken: @js(csrf_token()),
        sessionStoreUrl: @js(route('faculty.sessions.store')),
    })"
    x-init="init()"
    @keydown.escape.window="closePanels()"
>
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-slate-100">My Subjects</h1>
        <p class="text-gray-600 dark:text-slate-400 mt-2">Use <strong>Create a new subject</strong> when you need to add a course, then start attendance or enroll students.</p>
    </div>

    @php
        $facultyUser = auth()->user()->loadMissing('department');
        $subjectFormFieldErrors = ['code', 'name', 'description', 'units', 'section_name', 'day_of_week', 'start_time', 'end_time', 'department_id'];
        $showSubjectFormInitially = old('code') !== null || old('section_name') !== null;
        foreach ($subjectFormFieldErrors as $field) {
            if ($errors->has($field)) {
                $showSubjectFormInitially = true;
                break;
            }
        }
    @endphp

    <div class="mb-6" x-data="{ showSubjectForm: {{ $showSubjectFormInitially ? 'true' : 'false' }} }">
        <button
            type="button"
            x-show="!showSubjectForm"
            @click="showSubjectForm = true"
            class="inline-flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-semibold text-sm shadow-sm"
        >
            Create a new subject
        </button>

        <div
            x-show="showSubjectForm"
            x-cloak
            x-transition
            class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 border border-slate-200 dark:border-slate-600"
        >
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <h2 class="text-xl font-bold text-gray-800 dark:text-slate-100">Create a new subject</h2>
                <button type="button" @click="showSubjectForm = false" class="shrink-0 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 underline">
                    Hide form
                </button>
            </div>
            <p class="text-sm text-gray-600 dark:text-slate-400 mb-4">
                This adds a <strong>subject</strong> (course) and your <strong>class schedule</strong> for it. Subject code must be unique in the system.
                Type the <strong>section</strong> name; if it does not exist yet for the current school year and first semester, it will be created under your department.
                @if($facultyUser->department_id)
                    Subjects are filed under <strong>{{ $facultyUser->department?->name ?? 'your department' }}</strong>.
                @else
                    Choose the department this subject belongs to first.
                @endif
            </p>

        <form action="{{ route('faculty.subjects.store') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($subjectCreateDepartments->isNotEmpty())
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Department *</label>
                        <select name="department_id" required class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('department_id') border-red-500 @enderror">
                            <option value="" @selected(old('department_id') === null || old('department_id') === '')>—</option>
                            @foreach($subjectCreateDepartments as $dept)
                                <option value="{{ $dept->id }}" {{ (string) old('department_id') === (string) $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Subject code *</label>
                    <input type="text" name="code" value="{{ old('code') }}" required maxlength="255" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('code') border-red-500 @enderror">
                    @error('code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Subject name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="255" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Description</label>
                    <textarea name="description" rows="2" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Units *</label>
                    <input type="number" name="units" value="{{ old('units', 3) }}" required min="0" step="1" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('units') border-red-500 @enderror">
                    @error('units')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Section *</label>
                    <input type="text" name="section_name" value="{{ old('section_name') }}" required maxlength="255" class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('section_name') border-red-500 @enderror">
                    @error('section_name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Class days *</label>
                    <select name="day_of_week" required class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('day_of_week') border-red-500 @enderror">
                        <option value="" @selected(old('day_of_week') === null || old('day_of_week') === '')>—</option>
                        @foreach(\App\Models\Schedule::DAY_PATTERNS as $pat)
                            <option value="{{ $pat }}" {{ old('day_of_week') === $pat ? 'selected' : '' }}>
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
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Start *</label>
                        <input type="time" name="start_time" value="{{ old('start_time') }}" required class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('start_time') border-red-500 @enderror">
                        @error('start_time')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">End *</label>
                        <input type="time" name="end_time" value="{{ old('end_time') }}" required class="w-full px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg dark:bg-slate-900 dark:text-slate-100 @error('end_time') border-red-500 @enderror">
                        @error('end_time')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg font-semibold text-sm">
                    Create subject &amp; schedule
                </button>
            </div>
        </form>
        </div>
    </div>

    @if(session('enroll_flash_notes') && count(session('enroll_flash_notes')) > 0)
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-950/40 dark:border-amber-800 px-4 py-3 text-sm text-amber-950 dark:text-amber-100">
            <p class="font-semibold mb-2">Enrollment notes</p>
            <ul class="list-disc list-inside space-y-1 max-h-48 overflow-y-auto">
                @foreach(session('enroll_flash_notes') as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($pendingEnrollmentRequests->isNotEmpty())
        <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 dark:bg-slate-800 dark:border-blue-900 px-4 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="font-semibold text-slate-900 dark:text-slate-100">Enrollment requests</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">{{ $pendingEnrollmentRequests->count() }} student request(s) waiting for you.</p>
                </div>
                <a href="{{ route('faculty.enrollments.index') }}" class="inline-flex justify-center items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shrink-0">
                    Review requests
                </a>
            </div>
        </div>
    @endif

    <!-- Today's schedule: Philippine (app) timezone; polls so midnight & sessions stay current -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 mb-6 border border-slate-200 dark:border-slate-600">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800 dark:text-slate-100">
                Today's schedule (<span x-text="todayDateLabel"></span>)
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400" x-show="todayTimeDisplay">
                <span x-text="todayTimeDisplay"></span>
                <span x-text="todayTimezone ? ' · ' + todayTimezone.replace('_', ' ') : ''"></span>
                <span class="hidden sm:inline"> · auto-updates</span>
            </p>
        </div>

        <template x-if="todaySchedules.length > 0">
            <div class="space-y-4">
                <template x-for="sch in todaySchedules" :key="sch.id">
                    <div class="rounded-xl border-2 border-slate-200 dark:border-slate-600 bg-gradient-to-br from-white to-slate-50/90 dark:from-slate-800 dark:to-slate-900/60 shadow-sm hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow-md transition-all duration-200 p-4 md:p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between lg:gap-6">
                            <div class="flex-1 min-w-0 lg:max-w-3xl">
                                <button
                                    type="button"
                                    @click="openSchedule(sch.id, sch.sub_label)"
                                    class="text-left w-full rounded-lg px-3 py-2 -mx-1 -my-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900 group"
                                >
                                    <h3 class="font-semibold text-gray-900 dark:text-slate-100 text-lg group-hover:text-indigo-600 dark:group-hover:text-indigo-400" x-text="sch.course_name"></h3>
                                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5" x-text="sch.section_name"></p>
                                    <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-2 font-medium">Tap subject to enroll students</p>
                                </button>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mt-2 px-1">
                                    <span class="font-medium text-slate-700 dark:text-slate-300">Time:</span>
                                    <span x-text="sch.time_range"></span>
                                </p>
                                <template x-if="sch.room">
                                    <p class="text-sm text-slate-600 dark:text-slate-400 px-1">
                                        <span class="font-medium text-slate-700 dark:text-slate-300">Room:</span>
                                        <span x-text="sch.room"></span>
                                    </p>
                                </template>
                                <template x-if="sch.latest_session">
                                    <p class="text-xs text-slate-500 dark:text-slate-500 mt-2 px-1">
                                        Last session: <span x-text="sch.latest_session.started_at"></span>
                                        (<span x-text="sch.latest_session.attendance_count"></span> students marked)
                                    </p>
                                </template>
                            </div>
                            {{-- Actions: same height, no flex-stretch; wrap on small screens --}}
                            <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center lg:shrink-0 lg:justify-end" @click.stop>
                                <template x-if="sch.active_session_id">
                                    <a
                                        :href="sch.active_session_url"
                                        class="inline-flex h-11 shrink-0 items-center justify-center rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 sm:min-w-[10rem]"
                                    >
                                        View active session
                                    </a>
                                </template>
                                <template x-if="!sch.active_session_id">
                                    <form :action="sessionStoreUrl" method="POST" class="shrink-0 sm:min-w-[10rem]">
                                        <input type="hidden" name="_token" :value="csrfToken">
                                        <input type="hidden" name="schedule_id" :value="sch.id">
                                        <button
                                            type="submit"
                                            class="inline-flex h-11 w-full items-center justify-center rounded-lg bg-indigo-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 sm:w-auto sm:min-w-[10rem]"
                                        >
                                            Start attendance
                                        </button>
                                    </form>
                                </template>
                                <div class="flex gap-2 sm:ml-0">
                                    <a
                                        :href="sch.edit_url"
                                        class="inline-flex h-11 shrink-0 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-800 hover:bg-slate-50 dark:border-slate-500 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                    >
                                        Edit
                                    </a>
                                    <form
                                        :action="sch.destroy_url"
                                        method="POST"
                                        class="shrink-0"
                                        onsubmit="return confirm('Remove this subject and its schedule? Enrollment links to this class will be cleared.');"
                                    >
                                        <input type="hidden" name="_token" :value="csrfToken">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button
                                            type="submit"
                                            class="inline-flex h-11 items-center justify-center rounded-lg border border-red-200 bg-white px-4 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-900 dark:bg-slate-800 dark:text-red-300 dark:hover:bg-red-950/40"
                                        >
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>
        <template x-if="todaySchedules.length === 0">
            <div class="text-center py-8">
                <p class="text-gray-500 dark:text-slate-400">No classes scheduled for today.</p>
            </div>
        </template>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6 mb-6 border border-slate-200 dark:border-slate-600">
        <h2 class="text-xl font-bold text-gray-800 dark:text-slate-100 mb-2">Your subjects</h2>
        <p class="text-sm text-gray-600 dark:text-slate-400 mb-4">Tap a subject to enroll students. Start attendance from <strong>Today's schedule</strong> when that class meets today.</p>

        @if($flatSchedules->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
                @foreach($flatSchedules as $schedule)
                    @php
                        $rowLabel = ($schedule->course?->name ?? 'Subject').' — '.($schedule->section?->name ?? 'Section').' — '.$schedule->day_of_week.' '.$schedule->time_range;
                        $dayLabel = match ($schedule->day_of_week) {
                            'MWF' => 'Mon / Wed / Fri',
                            'TTH' => 'Tue / Thu',
                            'SAT' => 'Saturday',
                            'SUN' => 'Sunday',
                            default => $schedule->day_of_week,
                        };
                    @endphp
                    <div class="rounded-xl border-2 border-slate-200 dark:border-slate-600 bg-gradient-to-br from-white to-slate-50/90 dark:from-slate-800 dark:to-slate-900/50 shadow-sm hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow-md transition-all duration-200 flex flex-col overflow-hidden">
                        <button
                            type="button"
                            @click="openSchedule({{ $schedule->id }}, @js($rowLabel))"
                            class="group text-left px-4 py-4 flex-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-500"
                        >
                            <p class="font-semibold text-slate-900 dark:text-slate-100 text-base leading-snug group-hover:text-indigo-700 dark:group-hover:text-indigo-300">{{ $schedule->course?->name ?? '—' }}</p>
                            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ $schedule->section?->name ?? '—' }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-500 mt-2 font-medium">{{ $dayLabel }}</p>
                            <p class="text-sm text-slate-700 dark:text-slate-300 mt-0.5">{{ $schedule->time_range }}@if(filled($schedule->room))<span class="text-slate-400 dark:text-slate-500"> · </span><span class="text-slate-600 dark:text-slate-400">{{ $schedule->room }}</span>@endif</p>
                            <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-2 font-medium">Tap to enroll students</p>
                        </button>
                        <div class="flex flex-wrap gap-2 px-4 pb-4 pt-0 border-t border-slate-200/80 dark:border-slate-600/80" @click.stop>
                            <a href="{{ route('faculty.subjects.edit', $schedule) }}" class="inline-flex items-center px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-500 text-slate-800 dark:text-slate-200 text-sm font-medium hover:bg-slate-100 dark:hover:bg-slate-700">
                                Edit
                            </a>
                            <form action="{{ route('faculty.subjects.destroy', $schedule) }}" method="POST" class="inline"
                                  onsubmit="return confirm('Remove this subject and its schedule? Enrollment links to this class will be cleared.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center px-3 py-2 rounded-lg border border-red-200 dark:border-red-900 text-red-700 dark:text-red-300 text-sm font-medium hover:bg-red-50 dark:hover:bg-red-950/40">
                                    Remove
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 dark:text-slate-400">No subjects assigned yet.</p>
        @endif
    </div>

    <!-- Modal: step menu (centered, square cap — same width & max height) -->
    <div
        x-show="selectedId !== null && step === 'menu'"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[120] flex items-center justify-center p-4 sm:p-6 bg-slate-900/55"
        style="padding-bottom: max(1rem, env(safe-area-inset-bottom, 0px));"
        @click.self="closePanels()"
    >
        <div
            class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-600 flex flex-col overflow-hidden w-[min(20rem,calc(100vw-2rem),calc(100dvh-2rem))] h-[min(20rem,calc(100vw-2rem),calc(100dvh-2rem))] max-w-full max-h-full"
            @click.stop
            role="dialog"
            aria-modal="true"
            aria-labelledby="enroll-menu-title"
        >
            <div class="flex-1 min-h-0 overflow-y-auto p-5 sm:p-6 flex flex-col">
                <h3 id="enroll-menu-title" class="text-base font-bold text-slate-900 dark:text-slate-100 break-words leading-snug" x-text="selectedLabel"></h3>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-3 shrink-0">Add students from the whole platform to this class, or cancel.</p>
            </div>
            <div class="shrink-0 grid grid-cols-2 gap-2 p-4 sm:p-5 pt-0 border-t border-slate-200 dark:border-slate-600">
                <button type="button" @click="closePanels()" class="px-3 py-2.5 rounded-lg border border-slate-300 dark:border-slate-500 text-slate-800 dark:text-slate-200 text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700">
                    Cancel
                </button>
                <button type="button" @click="goEnroll()" class="px-3 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
                    Enroll a student
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: pick students (same centered square cap; inner areas scroll) -->
    <div
        x-show="selectedId !== null && step === 'pick'"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[120] flex items-center justify-center p-4 sm:p-6 bg-slate-900/55"
        style="padding-bottom: max(1rem, env(safe-area-inset-bottom, 0px));"
        @click.self="closePanels()"
    >
        <div
            class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-600 flex flex-col overflow-hidden w-[min(24rem,calc(100vw-2rem),calc(100dvh-2rem))] h-[min(24rem,calc(100vw-2rem),calc(100dvh-2rem))] max-w-full max-h-full"
            @click.stop
            role="dialog"
            aria-modal="true"
            aria-labelledby="enroll-pick-title"
        >
            <div class="shrink-0 p-4 sm:p-5 border-b border-slate-200 dark:border-slate-600">
                <h3 id="enroll-pick-title" class="text-base font-bold text-slate-900 dark:text-slate-100">Enroll students</h3>
                <p class="text-xs text-slate-600 dark:text-slate-400 mt-1 break-words line-clamp-2" x-text="selectedLabel"></p>
                <div class="mt-3 space-y-2">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-0.5">Search name</label>
                        <input type="search" x-model="qName" @input="scheduleFetchStudents()" @keydown.enter.prevent="fetchStudentsNow()" class="w-full min-w-0 px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm dark:bg-slate-900 dark:text-slate-100" placeholder="First or last name" autocomplete="off">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-0.5">Search student ID</label>
                        <input type="search" x-model="qUserId" @input="scheduleFetchStudents()" @keydown.enter.prevent="fetchStudentsNow()" class="w-full min-w-0 px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm dark:bg-slate-900 dark:text-slate-100" placeholder="Student ID" autocomplete="off">
                    </div>
                    <p class="text-[0.65rem] text-slate-500 dark:text-slate-400">Results update as you type.</p>
                    <div class="grid grid-cols-2 gap-1.5">
                        <button type="button" @click="clearStudentSearch()" class="px-2 py-2 rounded-lg border border-slate-300 dark:border-slate-500 text-xs font-medium text-slate-800 dark:text-slate-200">Clear</button>
                        <button type="button" @click="step = 'menu'" class="px-2 py-2 rounded-lg border border-slate-300 dark:border-slate-500 text-xs font-medium text-slate-800 dark:text-slate-200">Back</button>
                    </div>
                </div>
            </div>

            <form :action="`${base}/${selectedId}/enrollments/bulk`" method="POST" class="flex flex-col flex-1 min-h-0">
                @csrf
                <div class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden px-3 py-2 sm:px-4">
                    <p x-show="loading" class="text-sm text-slate-500 py-6 text-center">Loading students…</p>
                    <p x-show="!loading && students.length === 0 && searchHasTerms()" class="text-sm text-slate-600 dark:text-slate-400 py-6 text-center px-1">No student found.</p>
                    <p x-show="!loading && students.length === 0 && !searchHasTerms()" class="text-sm text-slate-500 py-6 text-center px-1">No students are available to enroll.</p>
                    <div x-show="!loading && students.length > 0" class="rounded-lg border border-slate-200 dark:border-slate-600 overflow-x-auto">
                        <table class="w-full text-xs min-w-0 border-collapse">
                            <thead class="text-left text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/50 sticky top-0">
                                <tr>
                                    <th class="w-9 py-2 pl-2 pr-1 align-middle" scope="col"><span class="sr-only">Select</span></th>
                                    <th class="py-2 pr-1 align-middle" scope="col">ID</th>
                                    <th class="py-2 pr-2 align-middle" scope="col">Name</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="s in students" :key="s.id">
                                    <tr class="border-t border-slate-100 dark:border-slate-700">
                                        <td class="w-9 py-2 pl-2 pr-1 align-middle">
                                            <label class="inline-flex cursor-pointer items-center leading-none">
                                                <input type="checkbox" name="student_ids[]" :value="s.id" class="size-4 shrink-0 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-500 dark:bg-slate-900">
                                            </label>
                                        </td>
                                        <td class="py-2 pr-1 align-middle font-mono break-all" x-text="s.user_id"></td>
                                        <td class="py-2 pr-2 align-middle text-slate-900 dark:text-slate-100 break-words" x-text="[s.last_name, s.first_name].filter(Boolean).join(', ')"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="shrink-0 grid grid-cols-2 gap-2 p-4 sm:p-5 pt-2 border-t border-slate-200 dark:border-slate-600">
                    <button type="button" @click="closePanels()" class="px-3 py-2.5 rounded-lg border border-slate-300 dark:border-slate-500 text-slate-800 dark:text-slate-200 text-sm font-medium">Cancel</button>
                    <button type="submit" class="px-3 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold">Enroll selected</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function facultySubjectEnroll(cfg) {
    const debounceMs = 320;
    const todayPollMs = 45000;
    return {
        base: cfg.base,
        todayDataUrl: cfg.todayDataUrl || '',
        todaySchedules: Array.isArray(cfg.todaySchedules) ? cfg.todaySchedules : [],
        todayDateLabel: cfg.todayDateLabel || '',
        todayTimeDisplay: '',
        todayTimezone: '',
        csrfToken: cfg.csrfToken || '',
        sessionStoreUrl: cfg.sessionStoreUrl || '',
        selectedId: null,
        step: null,
        selectedLabel: '',
        qName: '',
        qUserId: '',
        students: [],
        loading: false,
        _fetchDebounceTimer: null,
        _todayTimer: null,
        _todayVisHandler: null,
        _pageshowHandler: null,
        init() {
            this.setupTodayPolling();
        },
        setupTodayPolling() {
            if (!this.todayDataUrl) return;
            this.refreshTodaySchedules();
            this._todayTimer = setInterval(() => this.refreshTodaySchedules(), todayPollMs);
            this._todayVisHandler = () => {
                if (document.visibilityState === 'visible') {
                    this.refreshTodaySchedules();
                }
            };
            document.addEventListener('visibilitychange', this._todayVisHandler);
            this._pageshowHandler = (e) => {
                if (e.persisted) {
                    this.refreshTodaySchedules();
                }
            };
            window.addEventListener('pageshow', this._pageshowHandler);
        },
        async refreshTodaySchedules() {
            if (!this.todayDataUrl) return;
            try {
                const bust = '_=' + Date.now();
                const url = this.todayDataUrl + (this.todayDataUrl.includes('?') ? '&' : '?') + bust;
                const r = await fetch(url, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                if (!r.ok) return;
                const data = await r.json();
                if (typeof data.date_label === 'string') {
                    this.todayDateLabel = data.date_label;
                }
                this.todayTimeDisplay = data.time_display || '';
                this.todayTimezone = data.timezone || '';
                if (Array.isArray(data.schedules)) {
                    this.todaySchedules = data.schedules;
                }
            } catch (e) {
                /* keep last snapshot on network errors */
            }
        },
        searchHasTerms() {
            return (this.qName || '').trim() !== '' || (this.qUserId || '').trim() !== '';
        },
        openSchedule(id, label) {
            clearTimeout(this._fetchDebounceTimer);
            this.selectedId = id;
            this.selectedLabel = label;
            this.step = 'menu';
            this.students = [];
            this.qName = '';
            this.qUserId = '';
        },
        closePanels() {
            clearTimeout(this._fetchDebounceTimer);
            this.selectedId = null;
            this.step = null;
            this.students = [];
        },
        scheduleFetchStudents() {
            if (this.step !== 'pick' || !this.selectedId) return;
            clearTimeout(this._fetchDebounceTimer);
            this._fetchDebounceTimer = setTimeout(() => this.fetchStudents(), debounceMs);
        },
        fetchStudentsNow() {
            if (this.step !== 'pick' || !this.selectedId) return;
            clearTimeout(this._fetchDebounceTimer);
            this.fetchStudents();
        },
        clearStudentSearch() {
            this.qName = '';
            this.qUserId = '';
            clearTimeout(this._fetchDebounceTimer);
            this.fetchStudents();
        },
        async goEnroll() {
            clearTimeout(this._fetchDebounceTimer);
            this.step = 'pick';
            await this.fetchStudents();
        },
        async fetchStudents() {
            if (!this.selectedId) return;
            this.loading = true;
            try {
                const u = new URL(`${this.base}/${this.selectedId}/students-for-enrollment`);
                const qn = (this.qName || '').trim();
                const qu = (this.qUserId || '').trim();
                if (qn) u.searchParams.set('q_name', qn);
                if (qu) u.searchParams.set('q_user_id', qu);
                const r = await fetch(u.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await r.json();
                this.students = data.students || [];
            } catch (e) {
                this.students = [];
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
