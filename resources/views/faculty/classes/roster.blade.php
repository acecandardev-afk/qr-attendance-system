@extends('layouts.app')

@section('title', 'Class list')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <a href="{{ route('faculty.enrollments.index') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400 mb-2 inline-block">&larr; Back to class lists &amp; requests</a>
        <h1 class="text-3xl font-bold text-gray-800 dark:text-slate-100">Class list</h1>
        <p class="text-gray-600 dark:text-slate-300 mt-2">
            <span class="font-semibold text-gray-800 dark:text-slate-100">{{ $schedule->course?->code ?? 'Subject' }}</span>
            — {{ $schedule->section?->name ?? 'Section' }}
            · {{ $schedule->day_of_week }} {{ $schedule->time_range }}
        </p>
        <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">Students are listed alphabetically by last name. Only students you have approved for this class time appear here.</p>
    </div>

    <div class="bg-white dark:bg-slate-800/90 rounded-lg shadow overflow-hidden border border-transparent dark:border-slate-600/80">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-600">
                <thead class="bg-gray-50 dark:bg-slate-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">ID no.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Last name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">First name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase">Middle initial</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-600">
                    @forelse($enrollments as $enrollment)
                        @php $s = $enrollment->student; @endphp
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-slate-100">{{ $s?->user_id ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-slate-100">{{ $s?->last_name ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-slate-100">{{ $s?->first_name ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-slate-100">{{ $s?->middle_initial ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-gray-500 dark:text-slate-400">
                                No students on this list yet. Approve join requests on the main class page, or check that students picked this exact class time.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
