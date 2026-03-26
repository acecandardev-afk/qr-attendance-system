@extends('layouts.app')

@section('title', 'Edit Section')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('admin.sections.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
            ← Back to Sections
        </a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Edit Section</h1>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.sections.update', $section->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Section Name *</label>
                    <input type="text" name="name" value="{{ old('name', $section->name) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Department -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                    <select name="department_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('department_id') border-red-500 @enderror">
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $section->department_id) == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('department_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Year Level -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Year Level *</label>
                    <select name="year_level" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('year_level') border-red-500 @enderror">
                        <option value="1" {{ old('year_level', $section->year_level) == '1' ? 'selected' : '' }}>1st Year</option>
                        <option value="2" {{ old('year_level', $section->year_level) == '2' ? 'selected' : '' }}>2nd Year</option>
                        <option value="3" {{ old('year_level', $section->year_level) == '3' ? 'selected' : '' }}>3rd Year</option>
                        <option value="4" {{ old('year_level', $section->year_level) == '4' ? 'selected' : '' }}>4th Year</option>
                    </select>
                    @error('year_level')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Semester -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Semester *</label>
                    <select name="semester" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('semester') border-red-500 @enderror">
                        <option value="1st Sem" {{ old('semester', $section->semester) == '1st Sem' ? 'selected' : '' }}>1st Semester</option>
                        <option value="2nd Sem" {{ old('semester', $section->semester) == '2nd Sem' ? 'selected' : '' }}>2nd Semester</option>
                        <option value="Summer" {{ old('semester', $section->semester) == 'Summer' ? 'selected' : '' }}>Summer</option>
                    </select>
                    @error('semester')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- School Year -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">School Year *</label>
                    <input type="text" name="school_year" value="{{ old('school_year', $section->school_year) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('school_year') border-red-500 @enderror">
                    @error('school_year')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                    <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('status') border-red-500 @enderror">
                        <option value="active" {{ old('status', $section->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $section->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('admin.sections.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                    Update Section
                </button>
            </div>
        </form>
    </div>
</div>
@endsection