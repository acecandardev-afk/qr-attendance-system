@extends('layouts.app')

@section('title', 'Edit Enrollment')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('admin.enrollments.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
            ← Back to Enrollments
        </a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Edit Enrollment</h1>
        <p class="text-gray-600 mt-2 text-sm">Adjust class schedules for <span class="font-semibold">this</span> student only—others in the same section can differ.</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.enrollments.update', $enrollment->id) }}" method="POST" x-data="{
            sectionId: '{{ old('section_id', $enrollment->section_id) }}',
            schedules: @json($schedulesBySection),
            selected: @json(array_map('intval', (array) old('schedule_ids', $enrollment->schedules->pluck('id')->values()->all())))
        }">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <!-- Student -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Student *</label>
                    <select name="student_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('student_id') border-red-500 @enderror">
                        <option value="">Select Student</option>
                        @foreach($students as $student)
                            @continue(($student->role ?? null) !== 'student' || ($student->status ?? null) !== 'active' || !empty($student->deleted_at))
                            <option value="{{ $student->id }}" {{ old('student_id', $enrollment->student_id) == $student->id ? 'selected' : '' }}>
                                {{ $student->user_id }} - {{ $student->full_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('student_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Section -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Section *</label>
                    <select name="section_id" required x-model="sectionId" @change="selected = []" class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('section_id') border-red-500 @enderror">
                        <option value="">Select Section</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}" {{ old('section_id', $enrollment->section_id) == $section->id ? 'selected' : '' }}>
                                {{ $section->name }} ({{ $section->school_year }} - {{ $section->semester }})
                            </option>
                        @endforeach
                    </select>
                    @error('section_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Class schedules (optional) -->
                <div class="enrollment-schedule-copy">
                    <span class="enrollment-schedule-title">Class schedules</span>
                    <p class="enrollment-schedule-desc">Optional. Choose which class schedules apply to <span class="font-bold">this</span> student. If none are selected, the student counts for <span class="font-bold">all</span> classes in this section.</p>
                    <div class="schedule-picker space-y-2 rounded-xl p-4 max-h-64 overflow-y-auto mt-1">
                        <template x-for="row in (schedules[sectionId] || schedules[String(sectionId)] || [])" :key="row.id">
                            <label class="flex items-start gap-3 text-sm text-slate-800 cursor-pointer">
                                <input type="checkbox" name="schedule_ids[]" class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer shrink-0"
                                    :value="row.id"
                                    x-bind:checked="selected.includes(row.id)"
                                    @change="if ($event.target.checked) { if (!selected.includes(row.id)) selected.push(row.id) } else { selected = selected.filter(id => id !== row.id) }">
                                <span x-text="row.label"></span>
                            </label>
                        </template>
                        <p x-show="sectionId && (schedules[sectionId] || schedules[String(sectionId)] || []).length === 0" class="text-sm schedule-picker-hint text-slate-600">No active schedules are set up for this section yet.</p>
                        <p x-show="!sectionId" class="text-sm schedule-picker-hint text-slate-600">Select a section to see available schedules.</p>
                    </div>
                    @error('schedule_ids')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                    @error('schedule_ids.*')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- School Year -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">School Year *</label>
                    <input type="text" name="school_year" value="{{ old('school_year', $enrollment->school_year) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('school_year') border-red-500 @enderror">
                    @error('school_year')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Semester -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Semester *</label>
                    <select name="semester" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('semester') border-red-500 @enderror">
                        <option value="1st Sem" {{ old('semester', $enrollment->semester) == '1st Sem' ? 'selected' : '' }}>1st Semester</option>
                        <option value="2nd Sem" {{ old('semester', $enrollment->semester) == '2nd Sem' ? 'selected' : '' }}>2nd Semester</option>
                    </select>
                    @error('semester')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                    <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('status') border-red-500 @enderror">
                        <option value="enrolled" {{ old('status', $enrollment->status) === 'enrolled' ? 'selected' : '' }}>Enrolled</option>
                        <option value="dropped" {{ old('status', $enrollment->status) === 'dropped' ? 'selected' : '' }}>Dropped</option>
                        <option value="completed" {{ old('status', $enrollment->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                    @error('status')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('admin.enrollments.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                    Update Enrollment
                </button>
            </div>
        </form>
    </div>
</div>
@endsection