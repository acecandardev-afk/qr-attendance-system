@extends('layouts.app')

@section('title', 'Faculty Attendance Report')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('admin.reports.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
            ← Back to Reports
        </a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Faculty Attendance Report</h1>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('admin.reports.faculty') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Faculty *</label>
                <select name="faculty_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">Select Faculty</option>
                    @foreach($faculty as $fac)
                        <option value="{{ $fac->id }}" {{ request('faculty_id') == $fac->id ? 'selected' : '' }}>
                            {{ $fac->user_id }} - {{ $fac->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                    Generate Report
                </button>
            </div>
        </form>
    </div>

    @if($data)
        <!-- Summary Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-2xl font-bold text-gray-800">{{ $data['total_sessions'] }}</p>
                <p class="text-sm text-gray-600">Total Sessions</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-2xl font-bold text-green-600">{{ $data['active_sessions'] }}</p>
                <p class="text-sm text-gray-600">Active</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-2xl font-bold text-gray-600">{{ $data['closed_sessions'] }}</p>
                <p class="text-sm text-gray-600">Closed</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-2xl font-bold text-blue-600">{{ $data['total_attendance_records'] }}</p>
                <p class="text-sm text-gray-600">Total Attendance</p>
            </div>
        </div>

        <!-- By Course Breakdown -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Sessions by Course</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Sessions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Attendance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg per Session</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($data['by_course'] as $courseData)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $courseData['course']->code }} - {{ $courseData['course']->name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $courseData['total_sessions'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $courseData['total_attendance_records'] }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ $courseData['average_attendance_per_session'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Sessions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Sessions</h2>
            <div class="space-y-2">
                @foreach($data['recent_sessions'] as $session)
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <div>
                            <p class="font-semibold text-gray-800">{{ $session->schedule->course->name }}</p>
                            <p class="text-sm text-gray-600">
                                {{ $session->schedule->section->name }} • {{ $session->started_at->format('M j, Y - g:i A') }}
                            </p>
                        </div>
                        <div class="text-right">
                            <span class="px-3 py-1 rounded text-sm font-semibold
                                @if($session->status === 'active') bg-green-100 text-green-800
                                @elseif($session->status === 'closed') bg-gray-100 text-gray-800
                                @else bg-red-100 text-red-800
                                @endif">
                                {{ ucfirst($session->status) }}
                            </span>
                            <p class="text-sm text-gray-600 mt-1">{{ $session->attendanceRecords->count() }} students</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection