@extends('layouts.app')

@section('title', 'Manage Sections')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Manage Sections</h1>
            <p class="text-gray-600 mt-2">Manage class sections and groupings</p>
        </div>
        <a href="{{ route('admin.sections.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold">
            + Add Section
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('admin.sections.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                <select name="department_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">School Year</label>
                <input type="text" name="school_year" value="{{ request('school_year') }}" placeholder="e.g., 2024-2025" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                    Filter
                </button>
                <a href="{{ route('admin.sections.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Sections Table -->
    @php($bulkFormId = 'admin-bulk-sections')
    <div class="bg-white rounded-lg shadow overflow-hidden" x-data="window.adminBulkToolbar(@js($bulkFormId), 'sections')" x-init="syncCount()">
        <form id="{{ $bulkFormId }}" method="POST" action="{{ route('admin.sections.bulk-destroy') }}">
            @csrf
            @include('partials.admin-bulk-toolbar', ['itemLabel' => 'sections'])
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="w-12 pl-4 pr-2 py-3">
                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" aria-label="Select all on this page" @change="toggleAll($event.target.checked)">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Year Level</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Semester</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">School Year</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($sections as $section)
                    <tr>
                        <td class="w-12 pl-4 pr-2 py-4 align-middle">
                            <input type="checkbox" name="ids[]" value="{{ $section->id }}" class="bulk-cb rounded border-gray-300 text-blue-600 focus:ring-blue-500" @change="syncCount()">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $section->name }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $section->department->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $section->year_level }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $section->semester }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $section->school_year }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded
                                {{ $section->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($section->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('admin.sections.edit', $section->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                            @include('partials.confirm-action', [
                                'action' => route('admin.sections.destroy', $section->id),
                                'title' => 'Delete this section?',
                                'message' => 'This may affect schedules and enrollments linked to this section.',
                                'trigger' => 'Delete',
                                'confirm' => 'Delete',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                            No sections found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($sections->hasPages())
            <div class="px-6 py-4 bg-gray-50">
                {{ $sections->links() }}
            </div>
        @endif
        </form>
    </div>
</div>
@endsection