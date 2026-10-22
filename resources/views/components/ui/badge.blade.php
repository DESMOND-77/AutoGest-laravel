@props(['tone' => 'neutral', 'dot' => false, 'icon' => null])

@php
    $tones = [
        'neutral' => 'bg-hover text-ink-2',
        'accent' => 'bg-accent-soft text-accent',
        'success' => 'bg-success-soft text-success',
        'warning' => 'bg-warning-soft text-warning',
        'danger' => 'bg-danger-soft text-danger',
        'info' => 'bg-info-soft text-info',
    ];
    $dots = [
        'neutral' => 'bg-ink-3', 'accent' => 'bg-accent', 'success' => 'bg-success-solid',
        'warning' => 'bg-warning-solid', 'danger' => 'bg-danger-solid', 'info' => 'bg-info-solid',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-pill px-2 py-0.5 text-xs font-medium whitespace-nowrap', $tones[$tone] ?? $tones['neutral']]) }}>
    @if ($dot)
        <span class="size-1.5 rounded-full {{ $dots[$tone] ?? $dots['neutral'] }}" aria-hidden="true"></span>
    @elseif ($icon)
        <x-ui.icon :name="$icon" class="w-3.5 h-3.5" />
    @endif
    {{ $slot }}
</span>
