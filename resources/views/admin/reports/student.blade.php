@extends('layouts.app')

@section('title', 'Student Attendance Report')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('admin.reports.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
            ← Back to Reports
        </a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Student Attendance Report</h1>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('admin.reports.student') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Student *</label>
                <select name="student_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">Select Student</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                            {{ $student->user_id }} - {{ $student->full_name }}
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
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-2xl font-bold text-gray-800">{{ $data['total_records'] }}</p>
                <p class="text-sm text-gray-600">Total Records</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-2xl font-bold text-green-600">{{ $data['present'] }}</p>
                <p class="text-sm text-gray-600">Present</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-2xl font-bold text-yellow-600">{{ $data['late'] }}</p>
                <p class="text-sm text-gray-600">Late</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-2xl font-bold text-red-600">{{ $data['absent'] }}</p>
                <p class="text-sm text-gray-600">Absent</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-2xl font-bold text-blue-600">{{ $data['overall_attendance_rate'] }}%</p>
                <p class="text-sm text-gray-600">Attendance Rate</p>
            </div>
        </div>

        <!-- Export Button -->
        <div class="mb-6">
            <form action="{{ route('admin.reports.export.student') }}" method="POST" class="inline">
                @csrf
                <input type="hidden" name="student_id" value="{{ request('student_id') }}">
                <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg">
                    Export to CSV
                </button>
            </form>
        </div>

        <!-- By Course Breakdown -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Attendance by Course</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Course</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Present</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Late</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Absent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rate</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($data['by_course'] as $courseData)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $courseData['course']->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $courseData['total'] }}</td>
                                <td class="px-6 py-4 text-sm text-green-600">{{ $courseData['present'] }}</td>
                                <td class="px-6 py-4 text-sm text-yellow-600">{{ $courseData['late'] }}</td>
                                <td class="px-6 py-4 text-sm text-red-600">{{ $courseData['absent'] }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ $courseData['attendance_rate'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Records -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Attendance Records</h2>
            <div class="space-y-2">
                @foreach($data['recent_records'] as $record)
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                        <div>
                            <p class="font-semibold text-gray-800">{{ $record->attendanceSession->schedule->course->name }}</p>
                            <p class="text-sm text-gray-600">{{ $record->marked_at->format('M j, Y - g:i A') }}</p>
                        </div>
                        <span class="px-3 py-1 rounded text-sm font-semibold
                            @if($record->status === 'present') bg-green-100 text-green-800
                            @elseif($record->status === 'late') bg-yellow-100 text-yellow-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst($record->status) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection