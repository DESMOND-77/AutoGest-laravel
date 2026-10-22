{{-- Zone de notifications éphémères : annoncée poliment, jamais bloquante. --}}
<div
    x-data
    aria-live="polite"
    aria-atomic="false"
    class="pointer-events-none fixed inset-x-0 bottom-[calc(var(--dock-h)+env(safe-area-inset-bottom)+12px)] z-[90] flex flex-col items-center gap-2 px-4 lg:bottom-4 lg:items-end"
>
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div
            x-transition.duration.200ms
            x-on:mouseenter="$store.toasts.pause(toast.id)"
            class="pointer-events-auto flex w-full max-w-sm items-start gap-2.5 rounded-md border border-line bg-[var(--surface-solid)] p-3 shadow-[var(--elev-2)]"
            role="status"
        >
            <span
                class="mt-0.5"
                :class="{
                    'text-success': toast.tone === 'success',
                    'text-danger': toast.tone === 'danger',
                    'text-warning': toast.tone === 'warning',
                    'text-accent': !toast.tone || toast.tone === 'info'
                }"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 8v5M12 16h.01" />
                </svg>
            </span>
            <p class="flex-1 text-[13px] text-ink" x-text="toast.message"></p>
            <button type="button" class="rounded-sm p-1 text-ink-3 hover:text-ink focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]" x-on:click="$store.toasts.dismiss(toast.id)" aria-label="Fermer la notification">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="w-3.5 h-3.5" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    </template>
</div>
