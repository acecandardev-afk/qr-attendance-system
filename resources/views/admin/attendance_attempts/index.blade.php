@extends('layouts.app')

@section('title', 'Attendance Security Logs')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Attendance Security Logs</h1>
            <p class="text-gray-600 mt-2 text-sm">
                Review successful and failed QR scan attempts, including rate limits and network mismatches.
            </p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('admin.attendance-attempts.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                <input type="date" name="date" value="{{ request('date') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Result</label>
                <select name="result" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All</option>
                    <option value="success" @selected(request('result') === 'success')>Success</option>
                    <option value="invalid_token" @selected(request('result') === 'invalid_token')>Invalid Token</option>
                    <option value="expired" @selected(request('result') === 'expired')>Expired</option>
                    <option value="not_enrolled" @selected(request('result') === 'not_enrolled')>Not Enrolled</option>
                    <option value="duplicate" @selected(request('result') === 'duplicate')>Duplicate</option>
                    <option value="network_mismatch" @selected(request('result') === 'network_mismatch')>Network Mismatch</option>
                    <option value="rate_limited" @selected(request('result') === 'rate_limited')>Rate Limited</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Student ID</label>
                <input type="number" name="student_id" value="{{ request('student_id') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Attempts Table -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Scan Attempts</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Session / Course</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Result</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP / Network</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($attempts as $attempt)
                        <tr>
                            <td class="px-4 py-3 text-gray-900">
                                {{ $attempt->created_at->format('M j, Y g:i:s A') }}
                            </td>
                            <td class="px-4 py-3 text-gray-900">
                                @if($attempt->student)
                                    {{ $attempt->student->full_name }}
                                    <span class="block text-xs text-gray-500">ID: {{ $attempt->student->user_id }}</span>
                                @else
                                    <span class="text-xs text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-900">
                                @if($attempt->attendanceSession && $attempt->attendanceSession->schedule)
                                    {{ $attempt->attendanceSession->schedule->course->name ?? 'Course N/A' }}
                                    <span class="block text-xs text-gray-500">
                                        {{ $attempt->attendanceSession->schedule->section->name ?? 'Section N/A' }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-500">No session</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold
                                    @if($attempt->result === 'success') bg-green-100 text-green-800
                                    @else bg-red-100 text-red-800
                                    @endif">
                                    {{ ucfirst(str_replace('_', ' ', $attempt->result)) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-900">
                                <span class="block">{{ $attempt->ip_address }}</span>
                                <span class="block text-xs text-gray-500">{{ $attempt->network_identifier }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-900">
                                <span class="block text-xs text-gray-700 truncate max-w-xs">
                                    {{ $attempt->error_message ?? '—' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                No scan attempts found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $attempts->links() }}
        </div>
    </div>
</div>
@endsection

