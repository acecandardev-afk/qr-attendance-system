@extends('layouts.app')

@section('title', 'Classes you can join')

@section('content')
@php
    /** @var array<int, array<string, mixed>> $joinableCards */
@endphp
<script>
(function () {
    const cards = @json($joinableCards);
    window.studentBrowseClassesAlpine = function () {
        return {
            q: '',
            cards: Array.isArray(cards) ? cards : [],
            modalOpen: false,
            pick: null,
            filtered() {
                const t = this.q.trim().toLowerCase();
                if (!t) return this.cards;
                return this.cards.filter((c) => c.search.includes(t));
            },
            openAsk(c) {
                if (c.status === 'pending' || c.status === 'enrolled') return;
                this.pick = c;
                this.modalOpen = true;
            },
            closeAsk() {
                this.modalOpen = false;
                this.pick = null;
            },
        };
    };
})();
</script>

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 pb-8" x-data="studentBrowseClassesAlpine()">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-slate-100">Classes you can join</h1>
        <p class="text-gray-600 dark:text-slate-300 mt-2">Search by subject, section, schedule, or instructor, then ask your instructor to approve.</p>
    </div>

    <div class="bg-white dark:bg-slate-800/90 rounded-2xl shadow border border-gray-100 dark:border-slate-600/80 overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-slate-600/80">
            <label class="block text-sm font-semibold text-gray-800 dark:text-slate-200 mb-2" for="class-search-browse">Search classes</label>
            <input
                id="class-search-browse"
                type="search"
                x-model.debounce.300ms="q"
                placeholder="Type to search…"
                autocomplete="off"
                class="w-full px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-full text-sm bg-white dark:bg-slate-900 dark:text-slate-100 shadow-inner"
            >
        </div>
        <div class="max-h-[70vh] overflow-y-auto divide-y divide-gray-100 dark:divide-slate-600/80">
            <template x-for="c in filtered()" :key="c.id">
                <div class="p-4 sm:p-5 text-sm">
                    <div class="font-semibold text-gray-900 dark:text-slate-100" x-text="c.subject"></div>
                    <div class="text-gray-600 dark:text-slate-300 mt-0.5" x-text="c.section"></div>
                    <div class="text-gray-500 dark:text-slate-400 text-xs mt-1" x-text="c.when"></div>
                    <div class="text-gray-500 dark:text-slate-400 text-xs mt-0.5">Instructor: <span x-text="c.instructor"></span></div>
                    <div class="mt-3">
                        <template x-if="c.status === 'enrolled'">
                            <span class="inline-flex text-xs font-semibold px-2 py-1 rounded-full bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-100">You are in this class</span>
                        </template>
                        <template x-if="c.status === 'pending'">
                            <span class="inline-flex text-xs font-semibold px-2 py-1 rounded-full bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-100">Request sent — waiting</span>
                        </template>
                        <template x-if="!c.status">
                            <button
                                type="button"
                                @click="openAsk(c)"
                                class="w-full sm:w-auto text-center bg-blue-500 hover:bg-blue-600 text-white px-4 py-2.5 rounded-full text-sm font-semibold"
                            >
                                Ask to join
                            </button>
                        </template>
                    </div>
                </div>
            </template>
            <div x-show="filtered().length === 0" class="p-6 text-sm text-gray-500 dark:text-slate-400 text-center">
                No classes match your search.
            </div>
        </div>
    </div>

    <template x-teleport="body">
        <div
            x-show="modalOpen"
            x-cloak
            x-transition.opacity
            class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-900/50 p-4"
            style="display: none;"
            @click.self="closeAsk()"
            @keydown.escape.window="closeAsk()"
        >
            <div
                class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-600"
                role="dialog"
                aria-modal="true"
                @click.stop
            >
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Send a join request?</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300" x-show="pick">
                    You are about to ask to join
                    <strong x-text="pick?.subject"></strong>
                    <span x-show="pick?.section"> (<span x-text="pick?.section"></span>)</span>.
                    Your instructor will need to approve this before you show up on the class list.
                </p>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">You can cancel if you opened this by mistake.</p>
                <div class="mt-6 flex flex-wrap justify-end gap-3">
                    <button type="button" @click="closeAsk()" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-500 text-slate-700 dark:text-slate-200 font-medium hover:bg-slate-50 dark:hover:bg-slate-700">
                        Cancel
                    </button>
                    <form method="POST" action="{{ route('student.classes.join', [], false) }}" class="inline" x-show="pick">
                        @csrf
                        <input type="hidden" name="schedule_id" :value="pick?.id">
                        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700">
                            Yes, send request
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
@endsection
