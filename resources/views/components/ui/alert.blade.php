@props(['tone' => 'info', 'title' => null, 'dismissible' => false])

@php
    $tones = [
        'info' => 'bg-info-soft text-info border-info/30',
        'success' => 'bg-success-soft text-success border-success/30',
        'warning' => 'bg-warning-soft text-warning border-warning/30',
        'danger' => 'bg-danger-soft text-danger border-danger/40',
        'accent' => 'bg-accent-soft text-accent border-accent-line',
    ];
    $icons = ['info' => 'info', 'success' => 'circle-check', 'warning' => 'circle-alert', 'danger' => 'circle-alert', 'accent' => 'info'];
@endphp

<div
    {{ $attributes->class(['flex items-start gap-3 rounded-md border px-4 py-3 text-sm', $tones[$tone] ?? $tones['info']]) }}
    role="{{ $tone === 'danger' ? 'alert' : 'status' }}"
    @if ($dismissible) x-data="{ shown: true }" x-show="shown" @endif
>
    <x-ui.icon :name="$icons[$tone] ?? 'info'" class="w-5 h-5 mt-0.5" />
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div class="{{ $title ? 'mt-0.5 opacity-90' : '' }}">{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" @click="shown = false" class="-m-1 rounded-sm p-1 hover:opacity-70 focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]" aria-label="Masquer ce message">
            <x-ui.icon name="x" class="w-4 h-4" />
        </button>
    @endif
</div>
