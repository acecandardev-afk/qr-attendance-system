@extends('layouts.app')

@section('title', 'Manage Departments')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Departments</h1>
            <p class="text-gray-600 mt-2">Academic departments and programs</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(request('archived'))
                <a href="{{ route('admin.departments.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-semibold text-sm">Active only</a>
            @else
                <a href="{{ route('admin.departments.index', ['archived' => 1]) }}" class="bg-slate-600 hover:bg-slate-700 text-white px-4 py-2 rounded-lg font-semibold text-sm">View archived</a>
            @endif
            <a href="{{ route('admin.departments.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold">
                + Add department
            </a>
        </div>
    </div>

    <!-- Departments Table -->
    @php($bulkFormId = 'admin-bulk-departments')
    <div class="bg-white rounded-lg shadow overflow-hidden" @if(!request('archived')) x-data="window.adminBulkToolbar(@js($bulkFormId), 'departments', { requirePassword: true, verifyUrl: @js(route('admin.verify-current-password')) })" x-init="syncCount()" @endif>
        <form id="{{ $bulkFormId }}" method="POST" action="{{ route('admin.departments.bulk-destroy') }}">
            @csrf
            @if(!request('archived'))
                @include('partials.admin-bulk-toolbar', ['itemLabel' => 'departments', 'archive' => true, 'requirePassword' => true])
            @endif
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    @if(!request('archived'))
                    <th scope="col" class="w-12 pl-4 pr-2 py-3">
                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" aria-label="Select all on this page" @change="toggleAll($event.target.checked)">
                    </th>
                    @endif
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Number of listed courses</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Users</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($departments as $dept)
                    <tr>
                        @if(!request('archived'))
                        <td class="w-12 pl-4 pr-2 py-4 align-middle">
                            <input type="checkbox" name="ids[]" value="{{ $dept->id }}" class="bulk-cb rounded border-gray-300 text-blue-600 focus:ring-blue-500" @change="syncCount()">
                        </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $dept->code }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $dept->name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $dept->courses_number !== null ? $dept->courses_number : '—' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            {{ Str::limit($dept->description, 50) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $dept->users_count }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded
                                {{ $dept->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($dept->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if($dept->trashed())
                                <form method="POST" action="{{ route('admin.departments.restore', $dept->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-900 font-medium">Restore</button>
                                </form>
                            @else
                                <a href="{{ route('admin.departments.edit', $dept->id) }}" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                @include('partials.archive-with-password', [
                                    'action' => route('admin.departments.destroy', $dept->id),
                                    'title' => 'Archive this department?',
                                    'message' => 'It will be hidden from active lists. You can restore it later.',
                                    'trigger' => 'Archive',
                                    'confirm' => 'Archive',
                                    'triggerClass' => 'text-amber-700 hover:text-amber-900 text-sm font-medium',
                                ])
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ request('archived') ? 7 : 8 }}" class="px-6 py-8 text-center text-gray-500">
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