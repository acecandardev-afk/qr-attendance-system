@extends('layouts.app')

@section('title', 'Faculty')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Faculty</h1>
            <p class="text-gray-600 mt-2">Manage faculty accounts and employment status.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(request('archived'))
                <a href="{{ route('admin.faculties.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-semibold text-sm">Active only</a>
            @else
                <a href="{{ route('admin.faculties.index', ['archived' => 1]) }}" class="bg-slate-600 hover:bg-slate-700 text-white px-4 py-2 rounded-lg font-semibold text-sm">View archived</a>
            @endif
            <a href="{{ route('admin.faculties.print', request()->except('page')) }}" target="_blank" rel="noopener" class="bg-white border border-slate-300 text-slate-800 hover:bg-slate-50 px-4 py-2 rounded-lg font-semibold text-sm shadow-sm">
                Print
            </a>
            <a href="{{ route('admin.faculties.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold">
                + Add faculty
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('admin.faculties.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @if(request('archived'))
                <input type="hidden" name="archived" value="1">
            @endif
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ID no. / username</label>
                <input type="text" name="user_id" value="{{ request('user_id') }}" placeholder="Faculty ID (used to log in)"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                <select name="department_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">All</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ (string) request('department_id') === (string) $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name…"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Employment status</label>
                <select name="employment_status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">All</option>
                    <option value="part_time" {{ request('employment_status') === 'part_time' ? 'selected' : '' }}>Part-time</option>
                    <option value="temporary" {{ request('employment_status') === 'temporary' ? 'selected' : '' }}>Temporary</option>
                    <option value="regular" {{ request('employment_status') === 'regular' ? 'selected' : '' }}>Regular</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Account status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">All</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="md:col-span-2 lg:col-span-3 flex flex-wrap gap-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">Apply filters</button>
                <a href="{{ route('admin.faculties.index', array_filter(['archived' => request('archived')])) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID no.</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($faculties as $faculty)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $faculty->full_name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $faculty->user_id }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $faculty->department?->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ \App\Http\Controllers\Admin\FacultyController::employmentStatusLabel($faculty->employment_status) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded {{ $faculty->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($faculty->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if($faculty->trashed())
                                <form method="POST" action="{{ route('admin.faculties.restore', $faculty->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-900">Restore</button>
                                </form>
                            @else
                                <a href="{{ route('admin.faculties.edit', $faculty) }}" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                @include('partials.archive-with-password', [
                                    'action' => route('admin.faculties.destroy', $faculty),
                                    'title' => 'Archive this faculty member?',
                                    'message' => 'They will not be able to sign in until an administrator restores their account.',
                                    'trigger' => 'Archive',
                                    'confirm' => 'Archive',
                                ])
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">No faculty found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($faculties->hasPages())
            <div class="px-6 py-4 bg-gray-50">{{ $faculties->links() }}</div>
        @endif
    </div>
</div>
@endsection
