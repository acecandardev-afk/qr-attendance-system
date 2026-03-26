@extends('layouts.app')

@section('title', 'Section Attendance Report')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('admin.reports.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
            ← Back to Reports
        </a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Section Attendance Report</h1>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('admin.reports.section') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Section *</label>
                <select name="section_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" onchange="this.form.submit()">
                    <option value="">Select Section</option>
                    @foreach($sections as $section)
                        <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>
                            {{ $section->name }} ({{ $section->school_year }})
                        </option>
                    @endforeach
                </select>
            </div>

            @if($courses->count() > 0)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Course (Optional)</label>
                <select name="course_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Courses</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                            {{ $course->code }} - {{ $course->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

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
                <p class="text-2xl font-bold text-gray-800">{{ $data['total_students'] }}</p>
                <p class="text-sm text-gray-600">Enrolled Students</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-2xl font-bold text-blue-600">{{ round($data['overall_stats']['average_attendance_rate'], 2) }}%</p>
                <p class="text-sm text-gray-600">Average Attendance</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-2xl font-bold text-green-600">{{ $data['overall_stats']['total_present'] }}</p>
                <p class="text-sm text-gray-600">Total Present</p>
            </div>
        </div>

        <!-- Student Attendance Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-800">Student Attendance Summary</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sessions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Present</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Late</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Absent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attendance Rate</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($data['student_summary'] as $summary)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $summary['student']->user_id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $summary['student']->full_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $summary['total_sessions'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                    {{ $summary['present'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600">
                                    {{ $summary['late'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                    {{ $summary['absent'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex items-center">
                                        <span class="font-semibold 
                                            @if($summary['attendance_rate'] >= 90) text-green-600
                                            @elseif($summary['attendance_rate'] >= 75) text-yellow-600
                                            @else text-red-600
                                            @endif">
                                            {{ $summary['attendance_rate'] }}%
                                        </span>
                                        <div class="ml-2 w-24 bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full 
                                                @if($summary['attendance_rate'] >= 90) bg-green-600
                                                @elseif($summary['attendance_rate'] >= 75) bg-yellow-600
                                                @else bg-red-600
                                                @endif" 
                                                style="width: {{ $summary['attendance_rate'] }}%">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection