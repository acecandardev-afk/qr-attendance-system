@extends('layouts.app')

@section('title', 'Create Course')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('admin.courses.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
            ← Back to Courses
        </a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Create New Course</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.courses.store') }}" method="POST">
            @csrf

            <div class="space-y-6">
                <!-- Code -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Course Code *</label>
                    <input type="text" name="code" value="{{ old('code') }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('code') border-red-500 @enderror"
                           placeholder="e.g., IT312, CS201">
                    @error('code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Course Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('name') border-red-500 @enderror"
                           placeholder="e.g., Web Development 2">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="4"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('description') border-red-500 @enderror"
                              placeholder="Course description...">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Units -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Units *</label>
                    <input type="number" name="units" value="{{ old('units') }}" required min="1" max="10"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('units') border-red-500 @enderror">
                    @error('units')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Department -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                    <select name="department_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('department_id') border-red-500 @enderror">
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('department_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                    <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('status') border-red-500 @enderror">
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('admin.courses.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                    Create Course
                </button>
            </div>
        </form>
    </div>
</div>
@endsection