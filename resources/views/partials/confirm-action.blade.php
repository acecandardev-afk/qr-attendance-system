{{--
    In-app confirmation (replaces window.confirm — no "localhost says" banner).

    {!! view('partials.confirm-action', [
        'action' => route(...),
        'title' => 'Delete this schedule?',
        'message' => 'Optional detail text.',
        'trigger' => 'Delete',
        'confirm' => 'Delete',
        'triggerClass' => 'text-red-600 hover:text-red-900 text-sm font-medium',
        'confirmPlainPost' => true,   // POST-only actions; omit key for DELETE (default).
    ])->render() !!}
--}}
@php
    if (! isset($wrapperClass)) {
        $wrapperClass = 'inline';
    }
    $trigger = $trigger ?? 'Delete';
    $confirm = $confirm ?? 'Delete';
    $triggerClass = $triggerClass ?? 'text-red-600 hover:text-red-900 text-sm font-medium';
    $confirmButtonClass = $confirmButtonClass ?? 'px-4 py-2 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700';
    $dialogId = $dialogId ?? 'confirm-'.uniqid('', true);
@endphp
<div x-data="{ open: false }" class="{{ $wrapperClass }}">
    <button type="button" @click="open = true" class="{{ $triggerClass }}">{{ $trigger }}</button>
    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[200] flex items-center justify-center bg-slate-900/50 p-4"
            style="display: none;"
            @click.self="open = false"
            @keydown.escape.window="open = false"
        >
            <div
                class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 border border-slate-200"
                role="dialog"
                aria-modal="true"
                aria-labelledby="{{ $dialogId }}-title"
                @click.stop
            >
                <h3 id="{{ $dialogId }}-title" class="text-lg font-semibold text-slate-900">{{ $title }}</h3>
                @if(! empty($message))
                    <p class="mt-2 text-sm text-slate-600">{{ $message }}</p>
                @endif
                <div class="mt-6 flex flex-wrap justify-end gap-3">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 font-medium hover:bg-slate-50">
                        Cancel
                    </button>
                    <form method="POST" action="{{ $action }}" class="inline">
                        @csrf
                        @if(! ($confirmPlainPost ?? false))
                            @method('DELETE')
                        @endif
                        <button type="submit" class="{{ $confirmButtonClass }}">
                            {{ $confirm }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
