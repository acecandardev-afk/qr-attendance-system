@extends('layouts.app')

@section('title', 'Manage Departments')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Manage Departments</h1>
            <p class="text-gray-600 mt-2">Manage academic departments and programs</p>
        </div>
        <a href="{{ route('admin.departments.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold">
            + Add Department
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('success') }}
        </div>
    @endif

    <!-- Departments Table -->
    @php($bulkFormId = 'admin-bulk-departments')
    <div class="bg-white rounded-lg shadow overflow-hidden" x-data="window.adminBulkToolbar(@js($bulkFormId), 'departments')" x-init="syncCount()">
        <form id="{{ $bulkFormId }}" method="POST" action="{{ route('admin.departments.bulk-destroy') }}">
            @csrf
            @include('partials.admin-bulk-toolbar', ['itemLabel' => 'departments'])
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="w-12 pl-4 pr-2 py-3">
                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" aria-label="Select all on this page" @change="toggleAll($event.target.checked)">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Users</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Courses</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($departments as $dept)
                    <tr>
                        <td class="w-12 pl-4 pr-2 py-4 align-middle">
                            <input type="checkbox" name="ids[]" value="{{ $dept->id }}" class="bulk-cb rounded border-gray-300 text-blue-600 focus:ring-blue-500" @change="syncCount()">
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $dept->code }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $dept->name }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ Str::limit($dept->description, 50) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $dept->users_count }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $dept->courses_count }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded
                                {{ $dept->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($dept->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('admin.departments.edit', $dept->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                            @include('partials.confirm-action', [
                                'action' => route('admin.departments.destroy', $dept->id),
                                'title' => 'Delete this department?',
                                'message' => 'Courses, users, and sections tied to this department may need to be updated first.',
                                'trigger' => 'Delete',
                                'confirm' => 'Delete',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                            No departments found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($departments->hasPages())
            <div class="px-6 py-4 bg-gray-50">
                {{ $departments->links() }}
            </div>
        @endif
        </form>
    </div>
</div>
@endsection