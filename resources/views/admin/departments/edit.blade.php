@extends('layouts.app')

@section('title', 'Edit Department')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('admin.departments.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
            ← Back to Departments
        </a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Edit Department</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.departments.update', $department->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Department Code *</label>
                    <input type="text" name="code" value="{{ old('code', $department->code) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('code') border-red-500 @enderror">
                    @error('code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Department Name *</label>
                    <input type="text" name="name" value="{{ old('name', $department->name) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Number of listed courses</label>
                    <input type="number" name="courses_number" value="{{ old('courses_number', $department->courses_number) }}" min="0" max="999999" step="1"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg placeholder:text-gray-400/40 @error('courses_number') border-red-500 @enderror"
                           placeholder="example: 4">
                    @error('courses_number')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('description') border-red-500 @enderror">{{ old('description', $department->description) }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                    <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('status') border-red-500 @enderror">
                        <option value="active" {{ old('status', $department->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $department->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('admin.departments.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                    Update Department
                </button>
            </div>
        </form>
    </div>
</div>
@endsection