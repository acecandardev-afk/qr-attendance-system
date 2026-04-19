@extends('layouts.app')

@section('title', 'Add Student')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('admin.students.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
            ← Back to Students
        </a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">Add Student</h1>
        <p class="text-gray-600 mt-2 text-sm">Bulk import below, or add a single student when you need to.</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-6 border border-slate-200">
        <h2 class="text-lg font-bold text-gray-800 mb-2">Bulk import from Excel</h2>
        <p class="text-gray-600 text-sm mb-4">Use any spreadsheet you already have. The <strong>first row must be headers</strong>. We read common column titles (for example <strong>Student ID</strong>, <strong>ID No</strong>, <strong>LRN</strong>; <strong>First name</strong> / <strong>Last name</strong>, or a single <strong>Name</strong> / <strong>Full name</strong> column). Optional fields such as email, address, course, year level, age, birthday, and status are applied when those columns exist—everything else is ignored. Course must match a course name in your system exactly, or leave that column blank. Default password for new accounts is <span class="font-semibold">password</span> until the student changes it under <strong>Change password</strong>.</p>
        <form action="{{ route('admin.students.import') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
            @csrf
            <input type="file" name="file" id="import_file" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" class="sr-only" onchange="if (this.files.length) this.form.submit()">
            <label for="import_file" class="inline-flex items-center justify-center cursor-pointer bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-lg font-semibold text-sm shadow-sm">
                Bulk import from Excel
            </label>
            @error('file')
                <p class="text-red-500 text-xs">{{ $message }}</p>
            @enderror
        </form>
        <p class="text-xs text-gray-500 mt-3">Choose a file to start the import (up to {{ \App\Services\StudentExcelImportService::MAX_DATA_ROWS }} rows). A short summary appears on the student list when it finishes.</p>
    </div>

    <div class="mb-6" x-data="{ showAddForm: {{ $errors->any() ? 'true' : 'false' }} }">
        <button
            type="button"
            x-show="!showAddForm"
            @click="showAddForm = true"
            class="inline-flex items-center justify-center bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold text-sm shadow-sm"
        >
            Add a student
        </button>

        <div
            x-show="showAddForm"
            x-cloak
            x-transition
            class="bg-white rounded-lg shadow p-6 border border-slate-200"
        >
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Add one student</h2>
                    <p class="text-gray-600 text-sm mt-1">New accounts use the default password <span class="font-semibold">password</span> until you change it.</p>
                </div>
                <button type="button" @click="showAddForm = false" class="shrink-0 text-sm font-medium text-slate-600 hover:text-slate-900 underline">
                    Hide form
                </button>
            </div>

            <form action="{{ route('admin.students.store') }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Student ID *</label>
                        <input type="text" name="user_id" value="{{ old('user_id') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('user_id') border-red-500 @enderror">
                        @error('user_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email (optional)</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('email') border-red-500 @enderror" placeholder="Leave blank to auto-generate">
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">First name *</label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('first_name') border-red-500 @enderror">
                            @error('first_name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Middle name</label>
                            <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('middle_name') border-red-500 @enderror">
                            @error('middle_name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last name *</label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('last_name') border-red-500 @enderror">
                            @error('last_name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Course</label>
                        <select name="department_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('department_id') border-red-500 @enderror">
                            <option value="">Select course</option>
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

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Year level</label>
                            <input type="text" name="year_level" value="{{ old('year_level') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('year_level') border-red-500 @enderror" placeholder="e.g., 1st Year">
                            @error('year_level')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Birthday</label>
                            <input type="date" name="birthday" value="{{ old('birthday') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('birthday') border-red-500 @enderror">
                            @error('birthday')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Age</label>
                            <input type="number" name="age" min="1" max="150" value="{{ old('age') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('age') border-red-500 @enderror">
                            @error('age')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                            <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('status') border-red-500 @enderror">
                                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                        <input type="text" name="address" value="{{ old('address') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg @error('address') border-red-500 @enderror">
                        @error('address')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('admin.students.index') }}" class="inline-flex items-center bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">Cancel</a>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">Save student</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
