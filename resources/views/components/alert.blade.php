@props([
    'variant' => 'info',       // success | info | warning | error
    'title' => null,
    'dismissible' => false,
])

@php
    $variant = $variant === 'danger' ? 'error' : $variant;

    $map = [
        'success' => ['classes' => 'bg-success/10 text-success', 'icon' => 'document-check'],
        'info' => ['classes' => 'bg-info/10 text-info', 'icon' => 'bell'],
        'warning' => ['classes' => 'bg-warning/10 text-warning', 'icon' => 'exclamation-triangle'],
        'error' => ['classes' => 'bg-danger/10 text-danger', 'icon' => 'x-mark'],
    ];

    $config = $map[$variant] ?? $map['info'];
@endphp

<div
    @if ($dismissible) x-data="{ shown: true }" x-show="shown" x-cloak @endif
    {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-ui-md p-4 text-sm '.$config['classes']]) }}
    role="alert"
    data-icon="{{ $config['icon'] }}"
>
    <x-icon :name="$config['icon']" class="w-5 h-5 shrink-0 mt-0.5" />
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div @class(['mt-0.5' => $title])>{{ $slot }}</div>
    </div>
    @if ($dismissible)
        <button type="button" @click="shown = false" class="shrink-0 opacity-70 hover:opacity-100" aria-label="Fermer">
            <x-icon name="x-mark" class="w-4 h-4" />
        </button>
    @endif
</div>
