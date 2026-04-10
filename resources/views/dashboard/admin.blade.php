@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-800">Admin dashboard</h1>
        <p class="text-slate-600 mt-2">Welcome back, <span class="font-semibold text-slate-800">{{ $user->full_name }}</span></p>
    </div>

    <p class="text-sm text-slate-500 mb-4">School overview — tap the menu on the left to manage users, subjects, sections, and more.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div class="rounded-2xl p-5 shadow-lg border border-white/10" style="background:linear-gradient(135deg,#0e7490,#06b6d4);color:#fff;">
            <p class="text-xs font-bold uppercase tracking-wider opacity-90">Departments</p>
            <p class="text-3xl font-extrabold mt-2">{{ \App\Models\Department::count() }}</p>
        </div>
        <div class="rounded-2xl p-5 shadow-lg border border-white/10" style="background:linear-gradient(135deg,#5b21b6,#7c3aed);color:#fff;">
            <p class="text-xs font-bold uppercase tracking-wider opacity-90">Subjects</p>
            <p class="text-3xl font-extrabold mt-2">{{ \App\Models\Course::count() }}</p>
        </div>
        <div class="rounded-2xl p-5 shadow-lg border border-white/10" style="background:linear-gradient(135deg,#b45309,#d97706);color:#fff;">
            <p class="text-xs font-bold uppercase tracking-wider opacity-90">Sections</p>
            <p class="text-3xl font-extrabold mt-2">{{ \App\Models\Section::count() }}</p>
        </div>
        <div class="rounded-2xl p-5 shadow-lg border border-white/10" style="background:linear-gradient(135deg,#1d4ed8,#2563eb);color:#fff;">
            <p class="text-xs font-bold uppercase tracking-wider opacity-90">Class schedules</p>
            <p class="text-3xl font-extrabold mt-2">{{ \App\Models\Schedule::count() }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="rounded-2xl p-5 shadow-lg border border-white/10" style="background:linear-gradient(135deg,#1e40af,#3b82f6);color:#fff;">
            <p class="text-xs font-bold uppercase tracking-wider opacity-90">Students</p>
            <p class="text-3xl font-extrabold mt-2">{{ \App\Models\User::students()->count() }}</p>
        </div>
        <div class="rounded-2xl p-5 shadow-lg border border-white/10" style="background:linear-gradient(135deg,#047857,#10b981);color:#fff;">
            <p class="text-xs font-bold uppercase tracking-wider opacity-90">Faculty</p>
            <p class="text-3xl font-extrabold mt-2">{{ \App\Models\User::faculty()->count() }}</p>
        </div>
        <div class="rounded-2xl p-5 shadow-lg border border-white/10" style="background:linear-gradient(135deg,#7c2d12,#ea580c);color:#fff;">
            <p class="text-xs font-bold uppercase tracking-wider opacity-90">Admins</p>
            <p class="text-3xl font-extrabold mt-2">{{ \App\Models\User::where('role', 'admin')->count() }}</p>
        </div>
        <div class="rounded-2xl p-5 shadow-lg border border-slate-200 bg-slate-50 text-slate-800">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Quick tip</p>
            <p class="text-sm mt-2 leading-relaxed">Use <strong>Reports</strong> for daily attendance and <strong>Print</strong> on that page for a paper copy.</p>
        </div>
    </div>
</div>
@endsection
