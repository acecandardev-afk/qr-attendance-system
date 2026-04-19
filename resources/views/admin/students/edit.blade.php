@extends('layouts.app')

@section('title', 'Edit student')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('admin.students.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Students</a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Edit student</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.students.update', $student) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Student ID *</label>
                    <input type="text" name="user_id" value="{{ old('user_id', $student->user_id) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('user_id') border-red-500 @enderror">
                    @error('user_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                    <input type="email" name="email" value="{{ old('email', $student->email) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('email') border-red-500 @enderror">
                    @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">First name *</label>
                        <input type="text" name="first_name" value="{{ old('first_name', $student->first_name) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Middle name</label>
                        <input type="text" name="middle_name" value="{{ old('middle_name', $student->middle_name) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Last name *</label>
                        <input type="text" name="last_name" value="{{ old('last_name', $student->last_name) }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                    <select name="department_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">— None —</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ (string) old('department_id', $student->department_id) === (string) $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Year level</label>
                        <input type="text" name="year_level" value="{{ old('year_level', $student->year_level) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="e.g. 1st Year">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Birthday</label>
                        <input type="date" name="birthday" value="{{ old('birthday', $student->birthday?->format('Y-m-d')) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Age</label>
                        <input type="number" name="age" min="1" max="150" value="{{ old('age', $student->age) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                        <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="active" {{ old('status', $student->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $student->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                    <input type="text" name="address" value="{{ old('address', $student->address) }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">New password</label>
                    <input type="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('password') border-red-500 @enderror" placeholder="Leave blank to keep current" autocomplete="new-password">
                    @error('password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm new password</label>
                    <input type="password" name="password_confirmation" class="w-full px-4 py-2 border border-gray-300 rounded-lg" autocomplete="new-password">
                </div>
            </div>
            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('admin.students.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">Cancel</a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">Save changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
