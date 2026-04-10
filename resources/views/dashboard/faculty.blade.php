@extends('layouts.app')

@section('title', 'Faculty Dashboard')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="max-w-7xl mx-auto">

    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-extrabold text-slate-800">Faculty Dashboard</h1>
        <p class="text-slate-500 mt-1">Welcome back, <span class="font-semibold text-slate-700">{{ $user->full_name }}</span></p>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">

        {{-- Total Schedules --}}
        <div class="rounded-2xl p-5 flex items-center gap-4 border-2 border-white/20"
             style="background:linear-gradient(135deg,#1e40af,#3b6ff7) !important;color:#fff !important;box-shadow:0 8px 24px rgba(37,99,235,.35)">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:rgba(255,255,255,.18)">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider opacity-80">Total Schedules</p>
                <p class="text-3xl font-extrabold leading-none mt-1">{{ $totalSchedules }}</p>
            </div>
        </div>

        {{-- Active Classes Today --}}
        <div class="rounded-2xl p-5 flex items-center gap-4 border-2 border-white/20"
             style="background:linear-gradient(135deg,#065f46,#059669) !important;color:#fff !important;box-shadow:0 8px 24px rgba(5,150,105,.35)">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:rgba(255,255,255,.18)">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider opacity-80">Active Classes Today</p>
                <p class="text-3xl font-extrabold leading-none mt-1">{{ $activeSessionsToday }}</p>
            </div>
        </div>

        {{-- Total Students --}}
        <div class="rounded-2xl p-5 flex items-center gap-4 border-2 border-white/20"
             style="background:linear-gradient(135deg,#6d28d9,#8b5cf6) !important;color:#fff !important;box-shadow:0 8px 24px rgba(139,92,246,.35)">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:rgba(255,255,255,.18)">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider opacity-80">Total Students</p>
                <p class="text-3xl font-extrabold leading-none mt-1">{{ $totalStudents }}</p>
            </div>
        </div>

    </div>

    {{-- Attendance Chart --}}
    <div class="rounded-2xl p-6"
         style="background:rgba(255,255,255,0.96);border:1px solid rgba(148,163,184,.2);box-shadow:0 12px 30px rgba(2,6,23,.06)">
        <div class="flex items-center justify-between mb-6 flex-wrap gap-2">
            <div>
                <h2 class="text-lg font-extrabold text-slate-800">Daily Attendance Overview</h2>
                <p class="text-sm text-slate-500 mt-0.5">Last 14 days — Present, Late &amp; Absent</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-semibold">
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-3 h-3 rounded-full" style="background:#22c55e"></span>
                    Present
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-3 h-3 rounded-full" style="background:#f59e0b"></span>
                    Late
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="inline-block w-3 h-3 rounded-full" style="background:#ef4444"></span>
                    Absent
                </span>
            </div>
        </div>
        <div style="position:relative;height:280px">
            <canvas id="attendanceChart"></canvas>
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
                    backgroundColor: 'rgba(34,197,94,.75)',
                    borderColor: '#16a34a',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    label: 'Late',
                    data: late,
                    backgroundColor: 'rgba(245,158,11,.75)',
                    borderColor: '#d97706',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    label: 'Absent',
                    data: absent,
                    backgroundColor: 'rgba(239,68,68,.75)',
                    borderColor: '#dc2626',
                    borderWidth: 1.5,
                    borderRadius: 6,
                    borderSkipped: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(15,23,42,.92)',
                    titleColor: '#e2e8f0',
                    bodyColor: '#cbd5e1',
                    borderColor: 'rgba(148,163,184,.2)',
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 10,
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#94a3b8', font: { size: 11 } },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148,163,184,.12)' },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 11 },
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
