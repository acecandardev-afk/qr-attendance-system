@extends('layouts.app')

@section('title', 'Create Enrollment')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('admin.enrollments.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
            ← Back to Enrollments
        </a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Create New Enrollment</h1>
        <p class="text-gray-600 mt-2 text-sm">Each student can have a <span class="font-semibold">different</span> set of class schedules in the same section—pick the schedules that apply to this student only.</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.enrollments.store') }}" method="POST">
            @csrf

            <div class="space-y-6">
                <!-- Student -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Student *</label>
                    <select name="student_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('student_id') border-red-500 @enderror">
                        <option value="">Select Student</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
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
                    <select id="enrollment-section-id" name="section_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('section_id') border-red-500 @enderror">
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

                <!-- Class schedules (optional) -->
                <div class="enrollment-schedule-copy">
                    <span class="enrollment-schedule-title">Class schedules</span>
                    <p class="enrollment-schedule-desc">Optional. Select the specific courses or time slots for <span class="font-bold">this</span> student (another student in the same section can have a different selection). If you leave this empty, the student counts for <span class="font-bold">all</span> classes in this section.</p>
                    <div class="schedule-picker space-y-2 rounded-xl p-4 max-h-64 overflow-y-auto mt-1">
                        <div id="enrollment-schedule-options" class="space-y-2"></div>
                        <p id="enrollment-schedule-empty-selected" class="text-sm schedule-picker-hint text-slate-600 hidden">No schedules found for this section. Add one in the Schedules page first.</p>
                        <p id="enrollment-schedule-empty-unselected" class="text-sm schedule-picker-hint text-slate-600">Select a section to see available schedules.</p>
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
                    <input type="text" name="school_year" value="{{ old('school_year', '2024-2025') }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('school_year') border-red-500 @enderror"
                           placeholder="e.g., 2024-2025">
                    @error('school_year')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Semester -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Semester *</label>
                    <select name="semester" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('semester') border-red-500 @enderror">
                        <option value="">Select Semester</option>
                        <option value="1st Sem" {{ old('semester') == '1st Sem' ? 'selected' : '' }}>1st Semester</option>
                        <option value="2nd Sem" {{ old('semester') == '2nd Sem' ? 'selected' : '' }}>2nd Semester</option>
                        <option value="Summer" {{ old('semester') == 'Summer' ? 'selected' : '' }}>Summer</option>
                    </select>
                    @error('semester')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                    <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('status') border-red-500 @enderror">
                        <option value="enrolled" {{ old('status') === 'enrolled' ? 'selected' : '' }}>Enrolled</option>
                        <option value="dropped" {{ old('status') === 'dropped' ? 'selected' : '' }}>Dropped</option>
                        <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
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
                    Create Enrollment
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sectionSelect = document.getElementById('enrollment-section-id');
    const optionsWrap = document.getElementById('enrollment-schedule-options');
    const emptySelected = document.getElementById('enrollment-schedule-empty-selected');
    const emptyUnselected = document.getElementById('enrollment-schedule-empty-unselected');

    const schedulesBySection = @json($schedulesBySection);
    const selectedOnLoad = new Set(@json(array_map('intval', (array) old('schedule_ids', []))));

    function renderSchedules() {
        const sectionId = sectionSelect ? sectionSelect.value : '';
        const rows = schedulesBySection[String(sectionId)] || schedulesBySection[sectionId] || [];

        optionsWrap.innerHTML = '';

        if (!sectionId) {
            emptyUnselected.classList.remove('hidden');
            emptySelected.classList.add('hidden');
            return;
        }

        emptyUnselected.classList.add('hidden');

        if (!rows.length) {
            emptySelected.classList.remove('hidden');
            return;
        }

        emptySelected.classList.add('hidden');

        rows.forEach(function (row) {
            const label = document.createElement('label');
            label.className = 'flex items-start gap-3 text-sm text-slate-800 cursor-pointer';

            const input = document.createElement('input');
            input.type = 'checkbox';
            input.name = 'schedule_ids[]';
            input.value = row.id;
            input.className = 'mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer shrink-0';
            input.checked = selectedOnLoad.has(Number(row.id));

            const span = document.createElement('span');
            span.textContent = row.label;

            label.appendChild(input);
            label.appendChild(span);
            optionsWrap.appendChild(label);
        });
    }

    if (sectionSelect) {
        sectionSelect.addEventListener('change', function () {
            selectedOnLoad.clear();
            renderSchedules();
        });
    }

    renderSchedules();
});
</script>
@endpush