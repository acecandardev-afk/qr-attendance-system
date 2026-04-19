@extends('layouts.app')

@section('title', 'Attendance History')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('student.attendance.index', [], false) }}" class="text-blue-600 hover:text-blue-800 text-sm">
            ← Back to Scanner
        </a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Attendance History</h1>
        <p class="text-gray-600 mt-2">Complete record of your attendance</p>
    </div>

    <!-- Attendance Records Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Date & Time
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Course
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Section
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($records as $record)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $record->marked_at->format('M j, Y') }}<br>
                            <span class="text-xs text-gray-500">{{ $record->marked_at->format('g:i A') }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $record->attendanceSession?->schedule?->course?->name ?? 'Subject removed' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $record->attendanceSession?->schedule?->section?->name ?? 'Section removed' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($record->status === 'present') bg-green-100 text-green-800
                                @elseif($record->status === 'late') bg-yellow-100 text-yellow-800
                                @elseif($record->status === 'absent') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($record->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                            No attendance records found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination -->
        @if($records->hasPages())
            <div class="px-6 py-4 bg-gray-50">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</div>
@endsection