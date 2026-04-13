@extends('layouts.app')

@section('title', 'Manage Enrollments')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Manage Enrollments</h1>
            <p class="text-gray-600 mt-2">See who is enrolled in each section. Search updates automatically as you type.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('admin.enrollments.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4" x-data="{ debounceTimer: null }">
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Statuses</option>
                    <option value="enrolled" {{ request('status') == 'enrolled' ? 'selected' : '' }}>Enrolled</option>
                    <option value="dropped" {{ request('status') == 'dropped' ? 'selected' : '' }}>Dropped</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Find student</label>
                <input
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Type a name or ID number…"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg"
                    autocomplete="off"
                    @input="clearTimeout(debounceTimer); debounceTimer = setTimeout(() => $el.closest('form').submit(), 450)"
                >
            </div>

            <div class="md:col-span-4 flex flex-wrap items-end gap-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                    Filter
                </button>
                <a href="{{ route('admin.enrollments.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                    Reset
                </a>
            </div>
    </div>

    <!-- Enrollments Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Section</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schedules</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">School Year</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Semester</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($enrollments as $enrollment)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            @if($enrollment->student)
                                {{ $enrollment->student->full_name }}
                                @if($enrollment->student->trashed())
                                    <span class="block text-xs font-medium text-amber-700 mt-0.5">Student account removed</span>
                                @endif
                            @else
                                <span class="text-gray-500 italic">Unknown student</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $enrollment->student?->user_id ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            @if($enrollment->section)
                                {{ $enrollment->section->name }}
                                @if($enrollment->section->trashed())
                                    <span class="block text-xs font-medium text-amber-700 mt-0.5">Section removed</span>
                                @endif
                            @else
                                <span class="text-gray-500 italic">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-800 align-top max-w-xs">
                            @if($enrollment->schedules->isEmpty())
                                <span class="text-gray-600">All classes (section)</span>
                            @else
                                <ul class="list-disc list-inside space-y-0.5">
                                    @foreach($enrollment->schedules as $sch)
                                        <li>
                                            <span class="font-medium">{{ $sch->course?->code ?? 'Course' }}</span>
                                            <span class="text-gray-600"> — {{ $sch->day_of_week }} {{ $sch->time_range }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $enrollment->school_year }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $enrollment->semester }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded
                                @if($enrollment->status === 'enrolled') bg-green-100 text-green-800
                                @elseif($enrollment->status === 'dropped') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($enrollment->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                            No enrollments found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($enrollments->hasPages())
            <div class="px-6 py-4 bg-gray-50">
                {{ $enrollments->links() }}
            </div>
        @endif
        </form>
    </div>
</div>
@endsection