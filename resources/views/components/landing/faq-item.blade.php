@props(['question', 'answer'])

<div x-data="{ open: false }" class="border-b border-line py-5">
    <button
        type="button"
        @click="open = !open"
        :aria-expanded="open.toString()"
        class="flex w-full items-center justify-between gap-4 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-route focus-visible:ring-offset-2 focus-visible:ring-offset-cream rounded-sm"
    >
        <span class="font-display text-lg font-semibold uppercase tracking-wide text-ink">{{ $question }}</span>
        <svg
            class="h-5 w-5 shrink-0 text-route transition-transform duration-200"
            :class="{ 'rotate-45': open }"
            fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="mt-3 max-w-2xl text-sm leading-relaxed text-slate"
    >
        {{ $answer }}
    </div>
</div>
