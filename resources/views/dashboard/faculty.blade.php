@extends('layouts.app')

@section('title', 'Faculty Dashboard')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-6 sm:px-10 lg:px-14 py-2 sm:py-4">

    <header class="mb-10 sm:mb-12 pl-1 sm:pl-2">
        <h1 class="text-2xl sm:text-3xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">Faculty dashboard</h1>
        <p class="text-slate-600 dark:text-slate-300 mt-3 text-[15px] sm:text-base leading-relaxed max-w-2xl">
            Welcome back, <span class="font-medium text-slate-900 dark:text-white">{{ $user->full_name }}</span>
        </p>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-3 leading-relaxed max-w-2xl pl-0.5">
            Use the sidebar for classes, sessions, and reports.
        </p>
    </header>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 sm:gap-8 mb-10 sm:mb-12">
        <div class="rounded-2xl bg-white dark:bg-slate-800 px-7 py-7 sm:px-8 sm:py-8 shadow-sm">
            <p class="text-[11px] sm:text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Total schedules</p>
            <p class="text-3xl sm:text-4xl font-semibold tabular-nums tracking-tight text-slate-900 dark:text-white mt-4">{{ $totalSchedules }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-slate-800 px-7 py-7 sm:px-8 sm:py-8 shadow-sm">
            <p class="text-[11px] sm:text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Active classes today</p>
            <p class="text-3xl sm:text-4xl font-semibold tabular-nums tracking-tight text-slate-900 dark:text-white mt-4">{{ $activeSessionsToday }}</p>
        </div>
        <div class="rounded-2xl bg-white dark:bg-slate-800 px-7 py-7 sm:px-8 sm:py-8 shadow-sm">
            <p class="text-[11px] sm:text-xs font-semibold uppercase tracking-widest text-slate-500 dark:text-slate-400">Total students</p>
            <p class="text-3xl sm:text-4xl font-semibold tabular-nums tracking-tight text-slate-900 dark:text-white mt-4">{{ $totalStudents }}</p>
        </div>
    </div>

    <div class="rounded-2xl bg-white dark:bg-slate-800 px-6 py-8 sm:px-10 sm:py-10 lg:px-12 lg:py-11 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-5 sm:gap-8 mb-8 pl-1 sm:pl-2">
            <div>
                <h2 class="text-lg sm:text-xl font-semibold tracking-tight text-slate-900 dark:text-slate-50">Daily attendance</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2">Last 14 days</p>
            </div>
            <div class="flex flex-wrap gap-x-6 gap-y-2 text-xs sm:text-sm text-slate-600 dark:text-slate-300 pl-0.5 sm:pl-0">
                <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 shrink-0 rounded-sm bg-emerald-500" aria-hidden="true"></span> Present</span>
                <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 shrink-0 rounded-sm bg-amber-500" aria-hidden="true"></span> Late</span>
                <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 shrink-0 rounded-sm bg-red-500" aria-hidden="true"></span> Absent</span>
            </div>
        </div>
        <div class="relative h-[240px] sm:h-[280px] lg:h-[300px] w-full min-w-0 pl-1 sm:pl-2 pr-1">
            <canvas id="attendanceChart" aria-label="Attendance chart for the last 14 days"></canvas>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const labels  = @json($days);
    const present = @json($present);
    const late    = @json($late);
    const absent  = @json($absent);

    const ctx = document.getElementById('attendanceChart').getContext('2d');

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Present',
                    data: present,
                    backgroundColor: '#22c55e',
                    borderColor: '#16a34a',
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false,
                },
                {
                    label: 'Late',
                    data: late,
                    backgroundColor: '#f59e0b',
                    borderColor: '#d97706',
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false,
                },
                {
                    label: 'Absent',
                    data: absent,
                    backgroundColor: '#ef4444',
                    borderColor: '#dc2626',
                    borderWidth: 1,
                    borderRadius: 4,
                    borderSkipped: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 8,
                    right: 16,
                    top: 12,
                    bottom: 8,
                },
            },
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#f1f5f9',
                    bodyColor: '#cbd5e1',
                    borderColor: '#334155',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#64748b',
                        font: { size: 11, family: "'Inter', system-ui, sans-serif" },
                        padding: 10,
                    },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148, 163, 184, 0.35)' },
                    ticks: {
                        color: '#64748b',
                        font: { size: 11, family: "'Inter', system-ui, sans-serif" },
                        padding: 10,
                        stepSize: 1,
                        precision: 0,
                    },
                },
            },
        },
    });
})();
</script>
@endpush
