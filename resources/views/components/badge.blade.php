@props(['variant' => 'neutral'])

@php
    // Never color-alone: every variant pairs its tint with a dot, so status
    // stays legible without relying on hue perception (see brief §32).
    $variants = [
        'neutral' => 'bg-surface-elevated text-content-secondary',
        'success' => 'bg-success/10 text-success',
        'warning' => 'bg-warning/10 text-warning',
        'danger' => 'bg-danger/10 text-danger',
        'info' => 'bg-info/10 text-info',
        'primary' => 'bg-primary/10 text-primary',
    ];

    $dots = [
        'neutral' => 'bg-content-muted',
        'success' => 'bg-success',
        'warning' => 'bg-warning',
        'danger' => 'bg-danger',
        'info' => 'bg-info',
        'primary' => 'bg-primary',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium '.($variants[$variant] ?? $variants['neutral'])]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $dots[$variant] ?? $dots['neutral'] }}"></span>
    {{ $slot }}
</span>
