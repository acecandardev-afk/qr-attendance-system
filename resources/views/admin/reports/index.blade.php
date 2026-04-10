@extends('layouts.app')

@section('title', 'Attendance Reports Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Attendance Reports Dashboard</h1>
        <p class="text-gray-600 mt-2">Overview of attendance activity for a selected day.</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-col sm:flex-row gap-4 sm:items-end">
            <div class="w-full sm:w-64">
                <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                <input
                    type="date"
                    name="date"
                    value="{{ request('date', $dailyStats['date'] ?? now()->format('Y-m-d')) }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                >
            </div>

            <div class="flex flex-wrap gap-2 items-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                    View report
                </button>
                <a
                    href="{{ route('admin.reports.daily-print', ['date' => request('date', $dailyStats['date'] ?? now()->format('Y-m-d'))]) }}"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center bg-slate-700 hover:bg-slate-800 text-white px-6 py-2 rounded-lg"
                >
                    Print this day
                </a>
            </div>
        </form>
    </div>

    @if(!empty($dailyStats))
        <!-- Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-xs uppercase text-gray-500">Date</p>
                <p class="text-lg font-semibold text-gray-800 mt-1">
                    {{ \Carbon\Carbon::parse($dailyStats['date'])->format('M j, Y') }}
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-xs uppercase text-gray-500">Total Sessions</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ $dailyStats['total_sessions'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-xs uppercase text-gray-500">Attendance Marked</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">{{ $dailyStats['total_attendance_marked'] }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-xs uppercase text-gray-500">Present / Late / Absent</p>
                <p class="text-lg font-semibold mt-1">
                    <span class="text-green-600">{{ $dailyStats['present'] }}</span>
                    <span class="text-gray-500 mx-1">/</span>
                    <span class="text-yellow-600">{{ $dailyStats['late'] }}</span>
                    <span class="text-gray-500 mx-1">/</span>
                    <span class="text-red-600">{{ $dailyStats['absent'] }}</span>
                </p>
            </div>
        </div>

        <!-- Sessions Table -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <h2 class="text-xl font-bold text-gray-800">Sessions for {{ \Carbon\Carbon::parse($dailyStats['date'])->format('M j, Y') }}</h2>
                <p class="text-sm text-gray-500">
                    Showing {{ count($dailyStats['sessions']) }} session{{ count($dailyStats['sessions']) === 1 ? '' : 's' }}.
                </p>
            </div>

            @if(count($dailyStats['sessions']) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Section</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Faculty</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Started At</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Attendance</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($dailyStats['sessions'] as $session)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold text-gray-800">
                                            {{ $session->schedule->course->code ?? '' }} {{ $session->schedule->course->name ?? '' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $session->schedule->section->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $session->faculty->full_name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ optional($session->started_at)->format('g:i A') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                                            @if($session->status === 'active') bg-green-100 text-green-800
                                            @elseif($session->status === 'closed') bg-gray-100 text-gray-800
                                            @else bg-red-100 text-red-800
                                            @endif">
                                            {{ ucfirst($session->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-800">
                                        {{ $session->attendanceRecords->count() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-gray-500 text-center py-6">
                    No sessions found for this date.
                </p>
            @endif
        </div>
    @else
        <p class="text-gray-500">No data available.</p>
    @endif
</div>
@endsection