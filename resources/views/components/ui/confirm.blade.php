@props([
    'name' => 'confirm',
    'title' => 'Confirmer l\'action',
    'consequence' => null,
    'confirmLabel' => 'Confirmer',
    'typedConfirmation' => null,
    'action' => '#',
    'method' => 'DELETE',
])

<template x-teleport="#overlays">
    <div
        x-data="{ open: false, typed: '' }"
        x-show="open"
        x-cloak
        @open-confirm.window="if ($event.detail?.name === @js($name)) { open = true; typed = ''; $nextTick(() => document.getElementById('confirm-{{ $name }}-input')?.focus()); }"
        @keydown.escape.window="open = false"
        class="fixed inset-0 z-[80] flex items-center justify-center p-4"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="confirm-{{ $name }}-title"
        x-trap.inert.noscroll="open"
    >
        <div class="absolute inset-0 bg-[var(--scrim)]" @click="open = false" aria-hidden="true"></div>
        <div class="material-overlay relative w-full max-w-md p-5">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 rounded-full bg-danger-soft p-2 text-danger"><x-ui.icon name="exclamation-triangle" /></span>
                <div class="min-w-0">
                    <h2 id="confirm-{{ $name }}-title" class="text-section-title text-ink">{{ $title }}</h2>
                    @if ($consequence)
                        <p class="mt-1 text-sm text-ink-2">{{ $consequence }}</p>
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ $action }}" class="mt-4 space-y-3">
                @csrf
                @method($method)
                @if ($typedConfirmation)
                    <div>
                        <label for="confirm-{{ $name }}-input" class="block text-sm font-medium text-ink">
                            Saisissez « {{ $typedConfirmation }} » pour confirmer
                        </label>
                        <input id="confirm-{{ $name }}-input" type="text" x-model="typed" autocomplete="off"
                            class="mt-1 h-[var(--control-h)] w-full rounded-md border border-control bg-inset px-3 text-sm text-ink focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">
                    </div>
                @endif
                <div class="flex flex-wrap justify-end gap-2 pt-1">
                    <x-ui.button variant="ghost" type="button" x-on:click="open = false">Annuler</x-ui.button>
                    <x-ui.button variant="danger" type="submit" x-bind:disabled="typed !== @js($typedConfirmation ?? '')">
                        {{ $confirmLabel }}
                    </x-ui.button>
                </div>
            </form>
        </div>
    </div>
</template>
