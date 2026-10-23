@props([
    'name' => 'dialog',
    'title' => null,
    'description' => null,
    'size' => 'md',
    'open' => false,
])

@php
    $sizes = ['sm' => 'max-w-sm', 'md' => 'max-w-lg', 'lg' => 'max-w-2xl', 'xl' => 'max-w-4xl'];
@endphp

<template x-teleport="#overlays">
    <div
        x-data="{ open: @js((bool) $open) }"
        x-show="open"
        x-cloak
        @open-dialog.window="if ($event.detail?.name === @js($name)) open = true"
        @close-dialog.window="if ($event.detail?.name === @js($name)) open = false"
        @keydown.escape.window="open = false"
        class="fixed inset-0 z-[70] flex items-end justify-center sm:items-center sm:p-6"
        role="dialog"
        aria-modal="true"
        @if ($title) aria-labelledby="dlg-{{ $name }}-title" @endif
        x-trap.inert.noscroll="open"
    >
        <div class="absolute inset-0 bg-[var(--scrim)]" @click="open = false" aria-hidden="true"></div>

        <div
            x-show="open"
            x-transition:enter="transition duration-[var(--dur-2)] ease-[var(--ease-ui)]"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="material-overlay relative w-full rounded-t-xl sm:rounded-xl {{ $sizes[$size] ?? $sizes['md'] }} max-h-[92vh] overflow-y-auto"
        >
            <div class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                <div>
                    @if ($title)
                        <h2 id="dlg-{{ $name }}-title" class="text-section-title text-ink">{{ $title }}</h2>
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
                <div class="flex flex-wrap justify-end gap-2 border-t border-line px-5 py-3.5">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</template>
