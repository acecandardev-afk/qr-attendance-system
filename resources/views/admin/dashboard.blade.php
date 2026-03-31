@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <p class="text-xs uppercase tracking-widest text-indigo-600 font-semibold">NORSU-Guihulngan</p>
        <h1 class="text-4xl font-extrabold text-slate-900 mt-2">Admin Dashboard</h1>
        <p class="text-slate-600 mt-2">Overview of system activity, attendance health, and academic operations.</p>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-gradient-to-br from-slate-900 to-slate-700 text-white rounded-xl shadow p-5">
            <p class="text-xs uppercase tracking-wide text-slate-200">Total Users</p>
            <p class="mt-2 text-3xl font-extrabold">{{ $stats['total_users'] ?? 0 }}</p>
        </div>
        <div class="bg-gradient-to-br from-blue-600 to-indigo-600 text-white rounded-xl shadow p-5">
            <p class="text-xs uppercase tracking-wide text-blue-100">Students</p>
            <p class="mt-2 text-3xl font-extrabold">{{ $stats['total_students'] ?? 0 }}</p>
        </div>
        <div class="bg-gradient-to-br from-emerald-600 to-green-600 text-white rounded-xl shadow p-5">
            <p class="text-xs uppercase tracking-wide text-emerald-100">Faculty</p>
            <p class="mt-2 text-3xl font-extrabold">{{ $stats['total_faculty'] ?? 0 }}</p>
        </div>
        <div class="bg-gradient-to-br from-violet-600 to-purple-600 text-white rounded-xl shadow p-5">
            <p class="text-xs uppercase tracking-wide text-violet-100">Admins</p>
            <p class="mt-2 text-3xl font-extrabold">{{ $stats['total_admins'] ?? 0 }}</p>
        </div>
        <div class="bg-gradient-to-br from-cyan-600 to-sky-600 text-white rounded-xl shadow p-5">
            <p class="text-xs uppercase tracking-wide text-cyan-100">Departments</p>
            <p class="mt-2 text-3xl font-extrabold">{{ $stats['total_departments'] ?? 0 }}</p>
        </div>
        <div class="bg-gradient-to-br from-teal-600 to-emerald-600 text-white rounded-xl shadow p-5">
            <p class="text-xs uppercase tracking-wide text-teal-100">Courses</p>
            <p class="mt-2 text-3xl font-extrabold">{{ $stats['total_courses'] ?? 0 }}</p>
        </div>
        <div class="bg-gradient-to-br from-orange-500 to-amber-500 text-white rounded-xl shadow p-5">
            <p class="text-xs uppercase tracking-wide text-orange-100">Sections</p>
            <p class="mt-2 text-3xl font-extrabold">{{ $stats['total_sections'] ?? 0 }}</p>
        </div>
        <div class="bg-gradient-to-br from-indigo-600 to-blue-600 text-white rounded-xl shadow p-5">
            <p class="text-xs uppercase tracking-wide text-indigo-100">Schedules</p>
            <p class="mt-2 text-3xl font-extrabold">{{ $stats['total_schedules'] ?? 0 }}</p>
        </div>
    </div>

</div>
@endsection

