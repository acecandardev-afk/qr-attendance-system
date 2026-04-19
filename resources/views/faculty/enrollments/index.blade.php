@extends('layouts.app')

@section('title', 'Class lists & requests')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-slate-100">Class lists &amp; requests</h1>
        <p class="text-gray-600 dark:text-slate-300 mt-2">Students ask to join from their own account. When you approve, they appear on your class list. Use <strong>View list</strong> to see everyone in a specific class time, sorted by last name.</p>
    </div>

    @if(!empty($noTeachingSections ?? false))
        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-100 px-4 py-3 rounded-lg">
            You do not have any active class schedules yet. An administrator needs to set up your subjects and times before students can request to join.
        </div>
    @else
        @if(isset($mySchedules) && $mySchedules->isNotEmpty())
            <div class="bg-white dark:bg-slate-800/90 rounded-lg shadow p-6 mb-6 border border-transparent dark:border-slate-600/80">
                <h2 class="text-lg font-bold text-gray-800 dark:text-slate-100 mb-3">Your class times</h2>
                <ul class="divide-y divide-gray-100 dark:divide-slate-600/80">
                    @foreach($mySchedules as $sch)
                        <li class="py-3 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <span class="font-semibold text-gray-900 dark:text-slate-100">{{ $sch->course?->code ?? 'Subject' }}</span>
                                <span class="text-gray-600 dark:text-slate-300"> — {{ $sch->section?->name ?? 'Section' }}</span>
                                <p class="text-sm text-gray-500 dark:text-slate-400 mt-0.5">{{ $sch->day_of_week }} · {{ $sch->time_range }}</p>
                            </div>
                            <a href="{{ route('faculty.classes.roster', $sch) }}" class="shrink-0 inline-flex items-center justify-center bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-100 px-4 py-2 rounded-lg text-sm font-semibold">
                                View list
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(isset($pending) && $pending->isNotEmpty())
            <div class="bg-white dark:bg-slate-800/90 rounded-lg shadow p-6 mb-6 border border-transparent dark:border-slate-600/80">
                <h2 class="text-lg font-bold text-gray-800 dark:text-slate-100 mb-3">Waiting for you</h2>
                <p class="text-sm text-gray-600 dark:text-slate-300 mb-4">These students asked to join one of your classes. Approve to add them, or choose <strong>Not now</strong> if it was a mistake.</p>
                <ul class="space-y-4">
                    @foreach($pending as $req)
                        <li class="border border-gray-200 dark:border-slate-600 rounded-lg p-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-slate-100">{{ $req->student?->full_name ?? 'Student' }}</p>
                                <p class="text-sm text-gray-500 dark:text-slate-400">ID: {{ $req->student?->user_id ?? '—' }}</p>
                                <p class="text-sm text-gray-600 dark:text-slate-300 mt-1">{{ $req->section?->name ?? 'Section' }} · {{ $req->school_year }} · {{ $req->semester }}</p>
                                @if($req->schedules->isNotEmpty())
                                    <ul class="mt-2 text-sm text-gray-600 dark:text-slate-300 list-disc list-inside">
                                        @foreach($req->schedules as $sch)
                                            <li>{{ $sch->course?->code ?? 'Subject' }} — {{ $sch->day_of_week }} {{ $sch->time_range }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2 shrink-0">
                                {!! view('partials.confirm-action', [
                                    'action' => route('faculty.enrollments.approve', $req->id),
                                    'title' => 'Add this student to your class?',
                                    'message' => 'They will be able to check in when you run attendance for this class time.',
                                    'trigger' => 'Approve',
                                    'confirm' => 'Yes, add them',
                                    'confirmPlainPost' => true,
                                    'triggerClass' => 'inline-flex justify-center bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-semibold',
                                    'confirmButtonClass' => 'px-4 py-2 rounded-lg bg-emerald-600 text-white font-semibold hover:bg-emerald-700',
                                ])->render() !!}
                                {!! view('partials.confirm-action', [
                                    'action' => route('faculty.enrollments.decline', $req->id),
                                    'title' => 'Decline this request?',
                                    'message' => 'The student can send another request later if they need to.',
                                    'trigger' => 'Not now',
                                    'confirm' => 'Decline',
                                    'confirmPlainPost' => true,
                                    'triggerClass' => 'inline-flex justify-center border border-slate-300 dark:border-slate-500 text-slate-700 dark:text-slate-200 px-4 py-2 rounded-lg text-sm font-semibold hover:bg-slate-50 dark:hover:bg-slate-700',
                                    'confirmButtonClass' => 'px-4 py-2 rounded-lg bg-slate-700 text-white font-semibold hover:bg-slate-800',
                                ])->render() !!}
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endif
</div>
@endsection
