@extends('layouts.app')

@section('title', 'My Attendance Report')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">My Attendance Report</h1>
        <p class="text-gray-600 mt-2">Overview of your attendance sessions and statistics.</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('faculty.reports.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                <input
                    type="date"
                    name="start_date"
                    value="{{ request('start_date') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                <input
                    type="date"
                    name="end_date"
                    value="{{ request('end_date') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <div class="flex items-end">
                <div class="w-full flex flex-col gap-2">
                    <button
                        type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors"
                    >
                        Filter
                    </button>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                        <a
                            href="{{ route('faculty.reports.index', ['start_date' => now()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}"
                            class="inline-flex items-center justify-center bg-white hover:bg-gray-50 text-gray-800 px-4 py-2 rounded-lg border border-gray-300 text-sm font-semibold"
                        >
                            Today
                        </a>
                        <a
                            href="{{ route('faculty.reports.index') }}"
                            class="inline-flex items-center justify-center bg-white hover:bg-gray-50 text-gray-800 px-4 py-2 rounded-lg border border-gray-300 text-sm font-semibold"
                        >
                            All time
                        </a>
                        <a
                            href="{{ route('faculty.reports.export', request()->only(['start_date','end_date'])) }}"
                            class="inline-flex items-center justify-center bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-semibold"
                        >
                            Export CSV
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs uppercase text-gray-500">Total Classes</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ $data['total_sessions'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs uppercase text-gray-500">Active</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ $data['active_sessions'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs uppercase text-gray-500">Closed</p>
            <p class="text-2xl font-bold text-gray-700 mt-1">{{ $data['closed_sessions'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-xs uppercase text-gray-500">Total Attendance</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $data['total_attendance_records'] }}</p>
        </div>
    </div>

    <!-- Quick Access -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h2 class="text-xl font-bold text-gray-800">Quick Access</h2>
            <p class="text-sm text-gray-500">Drill down into class-level attendance details.</p>
        </div>
        <a
            href="{{ route('faculty.reports.class') }}"
            class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors"
        >
            View Class Attendance Reports
        </a>
    </div>

    <!-- Classes by Course -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Classes by Course</h2>
        @if($data['by_course']->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Classes</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Attendance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg per Session</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($data['by_course'] as $courseData)
                            <tr>
                                <td class="px-6 py-4 text-gray-900">
                                    {{ $courseData['course']->code ?? '' }} {{ $courseData['course']->name }}
                                </td>
                                <td class="px-6 py-4 text-gray-900">{{ $courseData['total_sessions'] }}</td>
                                <td class="px-6 py-4 text-gray-900">{{ $courseData['total_attendance_records'] }}</td>
                                <td class="px-6 py-4 font-semibold text-gray-900">{{ $courseData['average_attendance_per_session'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-gray-500 text-center py-4">No sessions found for the selected period.</p>
        @endif
    </div>

    <!-- Recent Classes -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Classes</h2>
        @if($data['recent_sessions']->count() > 0)
            <div class="space-y-2">
                @foreach($data['recent_sessions'] as $session)
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <div>
                            <p class="font-semibold text-gray-800">
                                {{ $session->schedule?->course?->name ?? 'Subject removed' }}
                            </p>
                            <p class="text-sm text-gray-600">
                                {{ $session->schedule?->section?->name ?? 'Section removed' }}
                                • {{ $session->started_at->format('M j, Y - g:i A') }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="px-3 py-1 rounded text-xs font-semibold
                                @if($session->status === 'active') bg-green-100 text-green-800
                                @elseif($session->status === 'closed') bg-gray-100 text-gray-800
                                @else bg-red-100 text-red-800
                                @endif">
                                {{ ucfirst($session->status) }}
                            </span>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ $session->attendanceRecords->count() }} students
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-center py-4">No Recent Classes.</p>
        @endif
    </div>
</div>
@endsection

