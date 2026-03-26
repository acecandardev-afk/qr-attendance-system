@extends('layouts.app')

@section('title', 'Student Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Student Dashboard</h1>
        <p class="text-gray-600 mt-2">Welcome back, {{ $user->full_name }}</p>
        <p class="text-sm text-gray-500">Student ID: {{ $user->user_id }}</p>
    </div>

    <!-- Mark Attendance Section -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Mark Attendance</h2>
        <p class="text-gray-600 mb-4">Scan the QR code displayed by your instructor to mark your attendance.</p>
        
        <a href="{{ route('student.attendance.index') }}" 
           class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold">
            Scan QR Code
        </a>
    </div>

    <!-- Enrolled Sections -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">My Sections</h2>
        
        @php
            $enrollments = $user->enrollments()->enrolled()->with('section.department')->get();
        @endphp

        @if($enrollments->count() > 0)
            <div class="space-y-3">
                @foreach($enrollments as $enrollment)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-800">{{ $enrollment->section->name }}</h3>
                        <p class="text-sm text-gray-600">{{ $enrollment->section->department->name }}</p>
                        <p class="text-sm text-gray-500">{{ $enrollment->school_year }} - {{ $enrollment->semester }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500">No active enrollments.</p>
        @endif
    </div>

    <!-- Attendance Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-600">Present</h3>
            <p class="text-3xl font-bold text-green-600 mt-2">{{ $user->attendanceRecords()->present()->count() }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-600">Late</h3>
            <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $user->attendanceRecords()->late()->count() }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-600">Absent</h3>
            <p class="text-3xl font-bold text-red-600 mt-2">{{ $user->attendanceRecords()->absent()->count() }}</p>
        </div>
    </div>
</div>
@endsection