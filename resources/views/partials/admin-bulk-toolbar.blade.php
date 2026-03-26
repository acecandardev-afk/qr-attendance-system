{{-- Parent: x-data="window.adminBulkToolbar('your-form-id', 'label')" wrapping this partial + form#id --}}
@props([
    'itemLabel' => 'items',
])

@include('partials.admin-bulk-delete-scripts')

<div class="flex flex-wrap items-center gap-3 px-6 py-3 bg-slate-50 border-b border-slate-200">
    <button
        type="button"
        @click="openConfirm()"
        :disabled="selectedCount === 0"
        class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-red-700 disabled:pointer-events-none disabled:opacity-40"
    >
        Delete selected
    </button>
    <span class="text-sm text-slate-600" x-show="selectedCount > 0" x-cloak>
        <span x-text="selectedCount"></span> selected
    </span>
</div>

<template x-teleport="body">
    <div
        x-show="confirmOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-900/50 p-4"
        style="display: none;"
        @click.self="confirmOpen = false"
        @keydown.escape.window="confirmOpen = false"
    >
        <div
            class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 border border-slate-200"
            role="dialog"
            aria-modal="true"
            @click.stop
        >
            <h3 class="text-lg font-semibold text-slate-900">Delete selected <span x-text="itemLabel"></span>?</h3>
            <p class="mt-2 text-sm text-slate-600">
                You are about to remove <strong x-text="selectedCount"></strong> record(s). This may affect related data (sessions, enrollments, etc.) depending on what you delete.
            </p>
            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button
                    type="button"
                    @click="confirmOpen = false"
                    class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 font-medium hover:bg-slate-50"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="confirmSubmit()"
                    class="px-4 py-2 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700"
                >
                    Yes, delete
                </button>
            </div>
        </div>
    </div>
</template>
