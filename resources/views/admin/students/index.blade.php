@extends('layouts.app')

@section('title', 'Students')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Students</h1>
            <p class="text-gray-600 mt-2">View and manage student accounts.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if(request('archived'))
                <a href="{{ route('admin.students.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-semibold text-sm">Active only</a>
            @else
                <a href="{{ route('admin.students.index', ['archived' => 1]) }}" class="bg-slate-600 hover:bg-slate-700 text-white px-4 py-2 rounded-lg font-semibold text-sm">View archived</a>
            @endif
            <a href="{{ route('admin.students.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold">
                + Add student
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <form method="GET" action="{{ route('admin.students.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4" x-data="{ debounceTimer: null }">
            @if(request('archived'))
                <input type="hidden" name="archived" value="1">
            @endif
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                <select name="department_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">All</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Account status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">All</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Search (ID or name)</label>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Student ID, first or last name…"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg" autocomplete="off"
                       @input="clearTimeout(debounceTimer); debounceTimer = setTimeout(() => $el.closest('form').submit(), 450)">
            </div>
            <div class="md:col-span-4 flex flex-wrap gap-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">Filter</button>
                <a href="{{ route('admin.students.index', array_filter(['archived' => request('archived')])) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">Reset</a>
            </div>
        </form>
    </div>

    @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
            <p class="font-semibold mb-2">Import notes (rows that were not added)</p>
            <ul class="list-disc list-inside space-y-1 max-h-60 overflow-y-auto">
                @foreach(session('import_errors') as $line)
                    <li>{{ $line }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Year level</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($students as $student)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $student->full_name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $student->user_id }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $student->email }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $student->department?->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $student->year_level ?? '—' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded {{ $student->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($student->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            @if($student->trashed())
                                <form method="POST" action="{{ route('admin.students.restore', $student->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-900">Restore</button>
                                </form>
                            @else
                                <a href="{{ route('admin.students.edit', $student) }}" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                @include('partials.archive-with-password', [
                                    'action' => route('admin.students.destroy', $student),
                                    'title' => 'Archive this student?',
                                    'message' => 'They will not be able to sign in until an administrator restores their account.',
                                    'trigger' => 'Archive',
                                    'confirm' => 'Archive',
                                ])
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">No students found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($students->hasPages())
            <div class="px-6 py-4 bg-gray-50">{{ $students->links() }}</div>
        @endif
    </div>
</div>
@endsection
