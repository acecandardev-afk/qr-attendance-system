@extends('layouts.app')

@section('title', 'Attendance Trends Report')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('admin.reports.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
            ← Back to Reports
        </a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Attendance Trends Report</h1>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('admin.reports.trends') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Section (Optional)</label>
                <select name="section_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Sections</option>
                    @foreach($sections as $section)
                        <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>
                            {{ $section->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                <input type="date" name="start_date" value="{{ request('start_date', $data['start_date']) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                <input type="date" name="end_date" value="{{ request('end_date', $data['end_date']) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                    Generate Report
                </button>
            </div>
        </form>
    </div>

    <!-- Trends Chart (Simple Table View) -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <h2 class="text-xl font-bold text-gray-800">Daily Attendance Trends</h2>
            <a
                href="{{ route('admin.reports.export.trends', request()->only(['section_id','start_date','end_date'])) }}"
                class="inline-flex items-center bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded-lg"
            >
                Export CSV
            </a>
        </div>
        <p class="text-sm text-gray-600 mb-4">{{ $data['start_date'] }} to {{ $data['end_date'] }}</p>

        @if($data['trends']->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Present</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Late</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Absent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Attendance Rate</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($data['trends'] as $trend)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ Carbon\Carbon::parse($trend['date'])->format('M j, Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $trend['total'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600">{{ $trend['present'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600">{{ $trend['late'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">{{ $trend['absent'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex items-center">
                                        <span class="font-semibold 
                                            @if($trend['attendance_rate'] >= 90) text-green-600
                                            @elseif($trend['attendance_rate'] >= 75) text-yellow-600
                                            @else text-red-600
                                            @endif">
                                            {{ $trend['attendance_rate'] }}%
                                        </span>
                                        <div class="ml-2 w-24 bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full 
                                                @if($trend['attendance_rate'] >= 90) bg-green-600
                                                @elseif($trend['attendance_rate'] >= 75) bg-yellow-600
                                                @else bg-red-600
                                                @endif" 
                                                style="width: {{ $trend['attendance_rate'] }}%">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Summary Statistics -->
            <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600">Average Daily Total</p>
                    <p class="text-2xl font-bold text-gray-800">{{ round($data['trends']->avg('total'), 2) }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600">Average Present</p>
                    <p class="text-2xl font-bold text-green-600">{{ round($data['trends']->avg('present'), 2) }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600">Average Late</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ round($data['trends']->avg('late'), 2) }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600">Average Attendance Rate</p>
                    <p class="text-2xl font-bold text-blue-600">{{ round($data['trends']->avg('attendance_rate'), 2) }}%</p>
                </div>
            </div>
        @else
            <p class="text-gray-500 text-center py-8">No attendance data found for the selected period</p>
        @endif
    </div>
</div>
@endsection