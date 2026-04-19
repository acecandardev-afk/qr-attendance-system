@extends('layouts.app')

@section('title', 'Edit faculty')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('admin.faculties.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Faculty</a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Edit faculty</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.faculties.update', $faculty) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">ID no. / username *</label>
                <input type="text" name="user_id" value="{{ old('user_id', $faculty->user_id) }}" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('user_id') border-red-500 @enderror">
                @error('user_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                <input type="email" name="email" value="{{ old('email', $faculty->email) }}" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('email') border-red-500 @enderror">
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">First name *</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $faculty->first_name) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Middle name</label>
                    <input type="text" name="middle_name" value="{{ old('middle_name', $faculty->middle_name) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Last name *</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $faculty->last_name) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                <select name="department_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">— None —</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ (string) old('department_id', $faculty->department_id) === (string) $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Employment status *</label>
                <select name="employment_status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    @foreach(['regular' => 'Regular', 'part_time' => 'Part-time', 'temporary' => 'Temporary'] as $val => $label)
                        <option value="{{ $val }}" {{ old('employment_status', $faculty->employment_status ?? 'regular') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Account status *</label>
                <select name="account_status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="active" {{ old('account_status', $faculty->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('account_status', $faculty->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">New password</label>
                <input type="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('password') border-red-500 @enderror" autocomplete="new-password" placeholder="Leave blank to keep current">
                @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Confirm new password</label>
                <input type="password" name="password_confirmation" class="w-full px-4 py-2 border border-gray-300 rounded-lg" autocomplete="new-password">
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('admin.faculties.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">Cancel</a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold">Update</button>
            </div>
        </form>
    </div>
</div>
@endsection
