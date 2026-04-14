@extends('layouts.app')

@section('title', 'Manage Schedules')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Class schedules</h1>
            <p class="text-gray-600 mt-2">When and where each subject meets</p>
        </div>
        <a href="{{ route('admin.schedules.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold">
            + Add schedule
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('admin.schedules.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Faculty</label>
                <select name="faculty_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Faculty</option>
                    @foreach($faculty as $fac)
                        <option value="{{ $fac->id }}" {{ request('faculty_id') == $fac->id ? 'selected' : '' }}>
                            {{ $fac->full_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Section</label>
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Class days</label>
                <select name="day_of_week" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">All patterns</option>
                    @foreach(\App\Models\Schedule::DAY_PATTERNS as $pat)
                        <option value="{{ $pat }}" {{ request('day_of_week') == $pat ? 'selected' : '' }}>{{ $pat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                    Filter
                </button>
                <a href="{{ route('admin.schedules.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Schedules Table -->
    @php($bulkFormId = 'admin-bulk-schedules')
    <div class="bg-white rounded-lg shadow overflow-hidden" x-data="window.adminBulkToolbar(@js($bulkFormId), 'schedules')" x-init="syncCount()">
        <form id="{{ $bulkFormId }}" method="POST" action="{{ route('admin.schedules.bulk-destroy') }}">
            @csrf
            @include('partials.admin-bulk-toolbar', ['itemLabel' => 'schedules', 'archive' => true])
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="w-12 pl-4 pr-2 py-3">
                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" aria-label="Select all on this page" @change="toggleAll($event.target.checked)">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Section</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Faculty</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Room</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($schedules as $schedule)
                    <tr>
                        <td class="w-12 pl-4 pr-2 py-4 align-middle">
                            <input type="checkbox" name="ids[]" value="{{ $schedule->id }}" class="bulk-cb rounded border-gray-300 text-blue-600 focus:ring-blue-500" @change="syncCount()">
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $schedule->course?->code ?? '—' }}
                            <br>
                            <span class="text-xs text-gray-500">{{ $schedule->course?->name ?? 'Course removed' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $schedule->section?->name ?? 'Section removed' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $schedule->faculty?->full_name ?? 'Faculty removed' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $schedule->day_of_week }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }} - 
                            {{ Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $schedule->room ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded
                                {{ $schedule->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($schedule->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('admin.schedules.edit', $schedule->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                            @include('partials.confirm-action', [
                                'action' => route('admin.schedules.destroy', $schedule->id),
                                'title' => 'Archive this class schedule?',
                                'message' => 'It will be hidden from the timetable. Past attendance records stay in the system.',
                                'trigger' => 'Archive',
                                'confirm' => 'Archive',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                            No schedules found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($schedules->hasPages())
            <div class="px-6 py-4 bg-gray-50">
                {{ $schedules->links() }}
            </div>
        @endif
        </form>
    </div>
</div>
@endsection