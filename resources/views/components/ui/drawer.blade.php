@props(['name' => 'drawer', 'title' => null, 'description' => null, 'size' => 'md', 'open' => false])

@php
    $sizes = ['sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-md', 'lg' => 'sm:max-w-xl', 'xl' => 'sm:max-w-2xl'];
@endphp

<template x-teleport="#overlays">
    <div
        x-data="{ open: {{ $open ? 'true' : 'false' }} }"
        x-show="open"
        x-cloak
        @open-drawer.window="if ($event.detail?.name === '{{ $name }}') open = true"
        @close-drawer.window="if ($event.detail?.name === '{{ $name }}') open = false"
        @keydown.escape.window="open = false"
        class="fixed inset-0 z-[70]"
        role="dialog"
        aria-modal="true"
        @if ($title) aria-labelledby="drw-{{ $name }}-title" @endif
        x-trap.inert.noscroll="open"
    >
        <div class="absolute inset-0 bg-[var(--scrim)]" @click="open = false" aria-hidden="true"></div>

        <div
            x-show="open"
            x-transition:enter="transition-transform duration-[var(--dur-3)] ease-[var(--ease-ui)]"
            x-transition:enter-start="translate-y-full sm:translate-y-0 sm:translate-x-full"
            x-transition:enter-end="translate-y-0 sm:translate-x-0"
            class="material-overlay absolute inset-x-0 bottom-0 max-h-[92vh] overflow-y-auto rounded-t-xl sm:inset-y-0 sm:left-auto sm:right-0 sm:h-full sm:max-h-none sm:w-full sm:rounded-l-xl sm:rounded-tr-none {{ $sizes[$size] ?? $sizes['md'] }}"
        >
            <div class="sticky top-0 flex items-start justify-between gap-4 border-b border-line bg-[var(--surface-overlay)] px-5 py-4 backdrop-blur">
                <div>
                    @if ($title)
                        <h2 id="drw-{{ $name }}-title" class="text-section-title text-ink">{{ $title }}</h2>
                    @endif
                    @if ($description)
                        <p class="mt-1 text-[13px] text-ink-2">{{ $description }}</p>
                    @endif
                </div>
                <button type="button" @click="open = false" class="-m-2 rounded-full p-2 text-ink-2 hover:bg-hover hover:text-ink focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]" aria-label="Fermer">
                    <x-ui.icon name="x" />
                </button>
            </div>
            <div class="px-5 py-4">{{ $slot }}</div>
            @isset($footer)
                <div class="sticky bottom-0 flex flex-wrap justify-end gap-2 border-t border-line bg-[var(--surface-overlay)] px-5 py-3.5">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</template>
