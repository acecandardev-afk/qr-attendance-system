@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8 rounded-2xl bg-gradient-to-br from-[#0f3b8c]/15 via-blue-50 to-amber-100/60 dark:from-[#152a52] dark:via-slate-900 dark:to-slate-950 px-5 py-6 sm:px-8 sm:py-7 shadow-xl shadow-[#0f3b8c]/20 dark:shadow-black/55">
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-slate-50 tracking-tight">Admin dashboard</h1>
        <p class="text-slate-700 dark:text-slate-200 mt-2 text-sm sm:text-base">
            Welcome back, <span class="font-semibold text-slate-900 dark:text-white">{{ $user->full_name }}</span>
        </p>
        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 mt-3 max-w-2xl leading-relaxed">
            Overview only — open Departments, Faculty, Students, and other tools from the <span class="font-medium text-slate-800 dark:text-slate-100">sidebar</span>.
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-5">
        <div class="rounded-2xl p-5 sm:p-6 bg-gradient-to-br from-blue-100 to-blue-50/90 dark:from-blue-900/55 dark:to-slate-950 shadow-lg shadow-blue-900/18 dark:shadow-black/50">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-blue-800 dark:text-blue-200">Departments</p>
                    <p class="text-3xl sm:text-4xl font-extrabold mt-2 tabular-nums text-slate-900 dark:text-white">{{ \App\Models\Department::count() }}</p>
                </div>
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-600/20 dark:bg-blue-400/25 text-blue-800 dark:text-blue-200 shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </span>
            </div>
        </div>

        <div class="rounded-2xl p-5 sm:p-6 bg-gradient-to-br from-indigo-100 to-indigo-50/90 dark:from-indigo-900/50 dark:to-slate-950 shadow-lg shadow-indigo-900/18 dark:shadow-black/50">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-indigo-800 dark:text-indigo-200">Faculty</p>
                    <p class="text-3xl sm:text-4xl font-extrabold mt-2 tabular-nums text-slate-900 dark:text-white">{{ \App\Models\User::faculty()->count() }}</p>
                </div>
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600/20 dark:bg-indigo-400/25 text-indigo-800 dark:text-indigo-200 shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </span>
            </div>
        </div>

        <div class="rounded-2xl p-5 sm:p-6 bg-gradient-to-br from-emerald-100 to-emerald-50/90 dark:from-emerald-900/45 dark:to-slate-950 shadow-lg shadow-emerald-900/18 dark:shadow-black/50">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-200">Students</p>
                    <p class="text-3xl sm:text-4xl font-extrabold mt-2 tabular-nums text-slate-900 dark:text-white">{{ \App\Models\User::students()->count() }}</p>
                </div>
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600/20 dark:bg-emerald-400/25 text-emerald-800 dark:text-emerald-200 shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                </span>
            </div>
        </div>

        <div class="rounded-2xl p-5 sm:p-6 bg-gradient-to-br from-amber-100 to-amber-50/90 dark:from-amber-900/40 dark:to-slate-950 shadow-lg shadow-amber-900/20 dark:shadow-black/50">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-amber-900 dark:text-amber-100">Reports</p>
                    <p class="text-lg sm:text-xl font-bold mt-2 text-slate-900 dark:text-white leading-snug">Student list<br><span class="text-sm font-semibold text-amber-900/95 dark:text-amber-100/90">Excel export</span></p>
                </div>
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/25 dark:bg-amber-400/20 text-amber-900 dark:text-amber-100 shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </span>
            </div>
        </div>

        <div class="rounded-2xl p-5 sm:p-6 bg-gradient-to-br from-slate-200/90 to-slate-100 dark:from-slate-700/80 dark:to-slate-950 shadow-lg shadow-slate-900/15 dark:shadow-black/50">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">Settings</p>
                    <p class="text-lg sm:text-xl font-bold mt-2 text-slate-900 dark:text-white leading-snug">Account &amp;<br><span class="text-sm font-semibold text-slate-700 dark:text-slate-300">attendance options</span></p>
                </div>
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-600/20 dark:bg-slate-500/30 text-slate-800 dark:text-slate-100 shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
            </div>
        </div>

        <div class="rounded-2xl p-5 sm:p-6 bg-gradient-to-br from-rose-100 to-rose-50/90 dark:from-rose-900/40 dark:to-slate-950 shadow-lg shadow-rose-900/18 dark:shadow-black/50">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-rose-800 dark:text-rose-200">Security</p>
                    <p class="text-lg sm:text-xl font-bold mt-2 text-slate-900 dark:text-white leading-snug">Attendance<br><span class="text-sm font-semibold text-rose-800 dark:text-rose-200">attempt log</span></p>
                </div>
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-600/20 dark:bg-rose-400/25 text-rose-800 dark:text-rose-200 shadow-inner">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
