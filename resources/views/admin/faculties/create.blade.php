@extends('layouts.app')

@section('title', 'Add faculty')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('admin.faculties.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Faculty</a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Add faculty</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.faculties.store') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ID no. / username *</label>
                <input type="text" name="user_id" value="{{ old('user_id') }}" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('user_id') border-red-500 @enderror"
                       placeholder="e.g. FAC-2024-001">
                @error('user_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('email') border-red-500 @enderror"
                       placeholder="Leave blank to use username@school.edu">
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">First name *</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('first_name') border-red-500 @enderror">
                    @error('first_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Middle name</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Last name *</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('last_name') border-red-500 @enderror">
                    @error('last_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                <select name="department_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">— None —</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ (string) old('department_id') === (string) $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
                @error('department_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Employment status *</label>
                <select name="employment_status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="regular" {{ old('employment_status', 'regular') === 'regular' ? 'selected' : '' }}>Regular</option>
                    <option value="part_time" {{ old('employment_status') === 'part_time' ? 'selected' : '' }}>Part-time</option>
                    <option value="temporary" {{ old('employment_status') === 'temporary' ? 'selected' : '' }}>Temporary</option>
                </select>
                @error('employment_status')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Account status *</label>
                <select name="account_status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="active" {{ old('account_status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('account_status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Password *</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('password') border-red-500 @enderror" autocomplete="new-password">
                @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm password *</label>
                <input type="password" name="password_confirmation" required class="w-full px-4 py-2 border border-gray-300 rounded-lg" autocomplete="new-password">
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('admin.faculties.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">Cancel</a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
