{{--
    Confirmation dialog for archiving (soft delete) — requires admin password.
    Used for faculty, student, department, etc. Archive stays disabled until verified.

    @include('partials.archive-with-password', [
        'action' => route('admin.faculties.destroy', $user),
        'title' => 'Archive this faculty member?',
        'message' => 'Optional detail.',
    ])
--}}
@php
    $trigger = $trigger ?? 'Archive';
    $confirm = $confirm ?? 'Archive';
    $triggerClass = $triggerClass ?? 'text-amber-700 hover:text-amber-900 text-sm font-medium';
    $dialogId = $dialogId ?? 'archive-pw-'.uniqid('', true);
@endphp
<div
    class="inline"
    x-data="{
        open: false,
        pw: '',
        archiveOk: false,
        verifying: false,
        verifySeq: 0,
        verifyUrl: @js(route('admin.verify-current-password')),
        reset() {
            this.pw = '';
            this.archiveOk = false;
            this.verifying = false;
            this.verifySeq++;
        },
        async verify() {
            if (!this.pw) {
                this.verifySeq++;
                this.archiveOk = false;
                this.verifying = false;
                return;
            }
            const seq = ++this.verifySeq;
            this.verifying = true;
            try {
                const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? '';
                const res = await fetch(this.verifyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ password: this.pw }),
                });
                let data = {};
                try {
                    data = await res.json();
                } catch (e) {
                    data = {};
                }
                if (seq !== this.verifySeq) {
                    return;
                }
                this.archiveOk = res.ok && data.valid === true;
            } catch (e) {
                if (seq === this.verifySeq) {
                    this.archiveOk = false;
                }
            } finally {
                if (seq === this.verifySeq) {
                    this.verifying = false;
                }
            }
        },
    }"
>
    <button type="button" @click="open = true; reset()" class="{{ $triggerClass }}">{{ $trigger }}</button>
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
            @click.self="open = false; reset()"
            @keydown.escape.window="open = false; reset()"
        >
            <div
                class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-600"
                role="dialog"
                aria-modal="true"
                aria-labelledby="{{ $dialogId }}-title"
                @click.stop
            >
                <h3 id="{{ $dialogId }}-title" class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $title }}</h3>
                @if(! empty($message))
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ $message }}</p>
                @endif
                <form method="POST" action="{{ $action }}" class="mt-4 space-y-4" @submit="if (!archiveOk) { $event.preventDefault() }">
                    @csrf
                    @method('DELETE')
                    <div>
                        <label for="{{ $dialogId }}-pw" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Your password</label>
                        <input
                            id="{{ $dialogId }}-pw"
                            type="password"
                            name="current_password"
                            x-model="pw"
                            @input.debounce.400ms="verify()"
                            autocomplete="current-password"
                            class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-900 dark:text-slate-100"
                            placeholder="Enter your password to confirm"
                        >
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400" x-show="pw && !archiveOk && !verifying" x-cloak>The Archive button unlocks when your password is correct.</p>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400" x-show="verifying" x-cloak>Checking…</p>
                    </div>
                    <div class="flex flex-wrap justify-end gap-3 pt-2">
                        <button type="button" @click="open = false; reset()" class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-500 text-slate-700 dark:text-slate-200 font-medium hover:bg-slate-50 dark:hover:bg-slate-700">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center min-w-[6.5rem] px-4 py-2.5 rounded-lg font-semibold text-sm transition"
                            :class="archiveOk
                                ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-sm cursor-pointer'
                                : 'bg-slate-200 text-slate-500 cursor-not-allowed opacity-90 dark:bg-slate-600 dark:text-slate-300'"
                            :aria-disabled="!archiveOk"
                            @click="if (!archiveOk) { $event.preventDefault() }"
                        >
                            {{ $confirm }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
