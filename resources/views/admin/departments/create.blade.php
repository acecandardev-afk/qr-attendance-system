@extends('layouts.app')

@section('title', 'Add department')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('admin.departments.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">← Back to Departments</a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Add department</h1>
        <p class="text-gray-600 mt-2 text-sm">Enter the department code and details. Codes are stored in uppercase.</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.departments.store') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Department code *</label>
                <input type="text" name="code" value="{{ old('code') }}" required maxlength="255" autocomplete="off"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg uppercase @error('code') border-red-500 @enderror"
                       placeholder="e.g. BSIT">
                @error('code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Department name *</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('name') border-red-500 @enderror"
                       placeholder="e.g. Bachelor of Science in Information Technology">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Number of listed courses</label>
                <input type="number" name="courses_number" value="{{ old('courses_number') }}" min="0" max="999999" step="1"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg placeholder:text-gray-400/40 @error('courses_number') border-red-500 @enderror"
                       placeholder="example: 4">
                @error('courses_number')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Short description</label>
                <textarea name="description" rows="3" maxlength="500"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('description') border-red-500 @enderror"
                          placeholder="Optional — brief summary">{{ old('description') }}</textarea>
                @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end gap-3">
                <a href="{{ route('admin.departments.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">Cancel</a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
