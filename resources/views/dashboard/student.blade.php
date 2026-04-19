@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="space-y-6 sm:space-y-8 min-w-0">

        <div class="rounded-2xl bg-gradient-to-br from-sky-200/80 via-blue-50 to-indigo-100/90 dark:from-sky-950/60 dark:via-slate-900 dark:to-indigo-950/50 px-5 py-6 sm:px-8 sm:py-7 shadow-xl shadow-sky-900/15 dark:shadow-black/55">
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-slate-50 tracking-tight">Welcome, {{ $user->full_name_without_middle }}</h1>
            <p class="text-slate-700 dark:text-slate-200 mt-2 text-sm sm:text-base">Quick overview of your classes and attendance.</p>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 mt-2 font-mono tabular-nums">ID: {{ $user->user_id }}</p>
            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 mt-5 max-w-2xl leading-relaxed pt-5 bg-white/40 dark:bg-slate-800/40 rounded-xl px-4 py-3 -mx-1">
                Use the <span class="font-semibold text-slate-900 dark:text-white">sidebar</span> to browse classes you can join, open the QR scanner, and view history — those actions are not available as buttons on this page.
            </p>
        </div>

        <div class="rounded-2xl bg-gradient-to-br from-blue-200/70 to-sky-100/80 dark:from-blue-950/70 dark:to-slate-950 p-5 sm:p-7 shadow-lg shadow-blue-900/15 dark:shadow-black/50">
            <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg shadow-blue-900/30">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                </span>
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Mark attendance</h2>
                    <p class="text-slate-700 dark:text-slate-200 mt-2 text-sm leading-relaxed">
                        When your instructor shares a check-in code, go to <span class="font-semibold text-slate-900 dark:text-white">Scan QR</span> in the sidebar to open the scanner.
                    </p>
                    @if(\Illuminate\Support\Str::startsWith((string) config('app.url'), 'https://'))
                        <p class="text-slate-800 dark:text-slate-100 mt-3 text-xs sm:text-sm font-medium bg-white/60 dark:bg-slate-900/50 rounded-lg px-3 py-2 border border-blue-200/80 dark:border-slate-600">
                            On your phone, use <span class="font-mono break-all">{{ rtrim((string) config('app.url'), '/') }}</span> (HTTPS) on the class Wi‑Fi — not plain <span class="font-mono">http://</span>.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="rounded-2xl bg-gradient-to-b from-white to-slate-100/90 dark:from-slate-800 dark:to-slate-950 p-5 sm:p-7 shadow-xl shadow-slate-900/12 dark:shadow-black/55">
            <h2 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-slate-100 mb-5 flex items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-200/80 dark:bg-indigo-500/35 text-indigo-900 dark:text-indigo-100 shadow-md shadow-indigo-900/10">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </span>
                My classes
            </h2>

            @if($myEnrollments->count() > 0)
                <ul class="space-y-4">
                    @foreach($myEnrollments as $enrollment)
                        <li class="rounded-xl bg-white/90 dark:bg-slate-800/90 p-4 sm:p-5 shadow-md shadow-slate-900/10 dark:shadow-black/40">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="font-semibold text-slate-900 dark:text-slate-100">{{ $enrollment->section?->name ?? 'Section no longer available' }}</h3>
                                    <p class="text-sm text-indigo-800 dark:text-indigo-200 mt-0.5">{{ $enrollment->section?->department?->name ?? '' }}</p>
                                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-1">{{ $enrollment->school_year }} · {{ $enrollment->semester }}</p>
                                    @if($enrollment->schedules->isNotEmpty())
                                        <ul class="mt-3 text-sm text-slate-700 dark:text-slate-200 space-y-1 rounded-lg bg-blue-50/90 dark:bg-blue-950/40 px-3 py-2">
                                            @foreach($enrollment->schedules as $sch)
                                                <li>{{ $sch->course?->code ?? 'Subject' }} — {{ $sch->day_of_week }} {{ $sch->time_range }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                                @if($enrollment->isPending())
                                    <span class="shrink-0 text-xs font-semibold px-3 py-1.5 rounded-full bg-amber-200/90 text-amber-950 dark:bg-amber-500/35 dark:text-amber-50 shadow-sm">Waiting for instructor</span>
                                @else
                                    <span class="shrink-0 text-xs font-semibold px-3 py-1.5 rounded-full bg-emerald-200/90 text-emerald-950 dark:bg-emerald-500/35 dark:text-emerald-50 shadow-sm">Enrolled</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="rounded-xl bg-slate-100/90 dark:bg-slate-800/70 px-4 py-8 text-center text-sm text-slate-700 dark:text-slate-200 shadow-inner">
                    You are not in any classes yet. To request enrollment, use <span class="font-semibold text-slate-900 dark:text-white">Classes you can join</span> in the sidebar.
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5">
            <div class="rounded-2xl p-5 sm:p-6 bg-gradient-to-br from-emerald-100 to-emerald-50/90 dark:from-emerald-900/50 dark:to-slate-950 shadow-lg shadow-emerald-900/18 dark:shadow-black/50">
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-900 dark:text-emerald-200">On time</p>
                <p class="text-3xl sm:text-4xl font-extrabold text-emerald-700 dark:text-emerald-300 mt-2 tabular-nums">{{ $user->attendanceRecords()->present()->count() }}</p>
            </div>
            <div class="rounded-2xl p-5 sm:p-6 bg-gradient-to-br from-amber-100 to-amber-50/90 dark:from-amber-900/45 dark:to-slate-950 shadow-lg shadow-amber-900/18 dark:shadow-black/50">
                <p class="text-xs font-bold uppercase tracking-wider text-amber-950 dark:text-amber-100">Late</p>
                <p class="text-3xl sm:text-4xl font-extrabold text-amber-700 dark:text-amber-300 mt-2 tabular-nums">{{ $user->attendanceRecords()->late()->count() }}</p>
            </div>
            <div class="rounded-2xl p-5 sm:p-6 bg-gradient-to-br from-rose-100 to-rose-50/90 dark:from-rose-900/45 dark:to-slate-950 shadow-lg shadow-rose-900/18 dark:shadow-black/50">
                <p class="text-xs font-bold uppercase tracking-wider text-rose-900 dark:text-rose-200">Absent</p>
                <p class="text-3xl sm:text-4xl font-extrabold text-rose-700 dark:text-rose-300 mt-2 tabular-nums">{{ $user->attendanceRecords()->absent()->count() }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
