@extends('layouts.app')

@section('title', 'Enrollments')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Student enrollments</h1>
            <p class="text-gray-600 mt-2">Enroll students in your sections and choose which of <span class="font-semibold">your</span> class schedules apply. Each student can have a different set of schedules.</p>
        </div>
        @if(empty($noTeachingSections ?? false))
            <a href="{{ route('faculty.enrollments.create') }}" class="inline-flex justify-center bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold">
                + Add enrollment
            </a>
        @endif
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            {{ session('error') }}
        </div>
    @endif

    @if(!empty($noTeachingSections ?? false))
        <div class="bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 rounded-lg">
            You do not have any active class schedules yet. An administrator must assign you to courses and schedules before you can manage enrollments here.
        </div>
    @else
    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('faculty.enrollments.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Section</label>
                <select name="section_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">All your sections</option>
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
                    <option value="">All statuses</option>
                    <option value="enrolled" {{ request('status') == 'enrolled' ? 'selected' : '' }}>Enrolled</option>
                    <option value="dropped" {{ request('status') == 'dropped' ? 'selected' : '' }}>Dropped</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>

            <div class="md:col-span-2 flex items-end space-x-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                    Filter
                </button>
                <a href="{{ route('faculty.enrollments.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Section</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Schedules</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">School year</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Semester</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
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
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('faculty.enrollments.edit', $enrollment->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                            @include('partials.confirm-action', [
                                'action' => route('faculty.enrollments.destroy', $enrollment->id),
                                'title' => 'Remove this enrollment?',
                                'message' => 'The student will no longer be listed for this section.',
                                'trigger' => 'Delete',
                                'confirm' => 'Remove',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                            No enrollments in your sections yet.
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
    </div>
    @endif
</div>
@endsection
