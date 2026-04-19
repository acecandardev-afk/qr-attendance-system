@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-800 dark:text-slate-100">My Profile</h1>
        <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">Your account details and shortcuts.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-sm p-6 space-y-4">
        <dl class="grid gap-3 text-sm">
            <div>
                <dt class="text-slate-500 dark:text-slate-400 font-medium">Name</dt>
                <dd class="text-slate-900 dark:text-slate-100 font-semibold">{{ $user->full_name }}</dd>
            </div>
            <div>
                <dt class="text-slate-500 dark:text-slate-400 font-medium">Faculty ID</dt>
                <dd class="text-slate-900 dark:text-slate-100 font-mono">{{ $user->user_id }}</dd>
            </div>
            <div>
                <dt class="text-slate-500 dark:text-slate-400 font-medium">Email</dt>
                <dd class="text-slate-900 dark:text-slate-100">{{ $user->email }}</dd>
            </div>
        </dl>

        <div class="pt-4 border-t border-slate-200 dark:border-slate-600 flex flex-col sm:flex-row gap-3">
            <a href="{{ route('settings.password.edit') }}" class="inline-flex justify-center items-center px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
                Change password
            </a>
            <a href="{{ route('faculty.settings.class-rules.edit') }}" class="inline-flex justify-center items-center px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-500 text-slate-800 dark:text-slate-200 text-sm font-semibold hover:bg-slate-50 dark:hover:bg-slate-700">
                Your class timing
            </a>
        </div>
    </div>
</div>
@endsection
