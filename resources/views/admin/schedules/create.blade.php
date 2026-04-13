@extends('layouts.app')

@section('title', 'Add class schedule')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('admin.schedules.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
            ← Back to class schedules
        </a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Add a class schedule</h1>
        <p class="text-gray-600 mt-1 text-sm">Pick the subject, section, teacher, and when the class meets (Mon/Wed/Fri, Tue/Thu, etc.).</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.schedules.store') }}" method="POST">
            @csrf

            <div class="space-y-6">
                <!-- Subject -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Subject *</label>
                    <select name="course_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('course_id') border-red-500 @enderror">
                        <option value="">Select subject</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->code }} - {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('course_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Section -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Section *</label>
                    <select name="section_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('section_id') border-red-500 @enderror">
                        <option value="">Select Section</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}" {{ old('section_id') == $section->id ? 'selected' : '' }}>
                                {{ $section->name }} ({{ $section->school_year }} - {{ $section->semester }})
                            </option>
                        @endforeach
                    </select>
                    @error('section_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Faculty -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Faculty *</label>
                    <select name="faculty_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('faculty_id') border-red-500 @enderror">
                        <option value="">Select Faculty</option>
                        @foreach($faculty as $fac)
                            <option value="{{ $fac->id }}" {{ old('faculty_id') == $fac->id ? 'selected' : '' }}>
                                {{ $fac->full_name }} ({{ $fac->user_id }})
                            </option>
                        @endforeach
                    </select>
                    @error('faculty_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Meeting pattern -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Class days *</label>
                    <select name="day_of_week" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('day_of_week') border-red-500 @enderror">
                        <option value="">Select pattern</option>
                        @foreach(\App\Models\Schedule::DAY_PATTERNS as $pat)
                            <option value="{{ $pat }}" {{ old('day_of_week') == $pat ? 'selected' : '' }}>
                                @switch($pat)
                                    @case('MWF') Mon / Wed / Fri @break
                                    @case('TTH') Tue / Thu @break
                                    @default {{ $pat }}
                                @endswitch
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Choose how often this class meets during the week.</p>
                    @error('day_of_week')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Start Time -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Time *</label>
                    <input type="time" name="start_time" value="{{ old('start_time') }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('start_time') border-red-500 @enderror">
                    @error('start_time')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- End Time -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Time *</label>
                    <input type="time" name="end_time" value="{{ old('end_time') }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('end_time') border-red-500 @enderror">
                    @error('end_time')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Room -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Room</label>
                    <input type="text" name="room" value="{{ old('room') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('room') border-red-500 @enderror"
                           placeholder="e.g., IT-LAB-301">
                    @error('room')
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
                <a href="{{ route('admin.schedules.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                    Save schedule
                </button>
            </div>
        </form>
    </div>
</div>
@endsection