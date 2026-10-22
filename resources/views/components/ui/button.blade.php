@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconRight' => null,
    'href' => null,
    'type' => 'button',
    'loading' => false,
    'label' => null,
])

@php
    $variants = [
        'primary' => 'bg-accent text-on-accent shadow-sm hover:bg-accent-hover active:shadow-none',
        'secondary' => 'bg-solid text-ink border border-control/50 hover:bg-hover',
        'ghost' => 'text-ink-2 hover:bg-hover hover:text-ink',
        'danger' => 'bg-danger-solid text-on-solid hover:opacity-90',
        'link' => 'text-accent underline underline-offset-2 hover:text-accent-hover px-0',
    ];
    $sizes = [
        'sm' => 'h-9 px-3 text-[13px] gap-1.5',
        'md' => 'h-[var(--control-h)] px-4 text-sm gap-2',
        'lg' => 'h-12 px-6 text-[15px] gap-2',
    ];
    $classes = 'inline-flex items-center justify-center rounded-pill font-medium transition-colors duration-[var(--dur-1)] focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)] disabled:opacity-50 disabled:pointer-events-none';
    $base = $classes.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
    $iconOnly = (trim((string) $slot) === '' && $icon);
@endphp

@if ($href)
    <a href="{{ $href }}" @if ($iconOnly) aria-label="{{ $label }}" @endif {{ $attributes->class([$base, $iconOnly ? 'px-0 aspect-square' : null]) }}>
        @if ($icon)<x-ui.icon :name="$icon" />@endif
        @if (trim((string) $slot) !== '')<span>{{ $slot }}</span>@endif
        @if ($iconRight)<x-ui.icon :name="$iconRight" />@endif
    </a>
@else
    <button
        type="{{ $type }}"
        @if ($iconOnly) aria-label="{{ $label }}" @endif
        @if ($loading) aria-busy="true" @endif
        {{ $attributes->class([$base, $iconOnly ? 'px-0 aspect-square' : null]) }}
    >
        @if ($loading)
            <x-ui.spinner class="w-4 h-4" />
        @elseif ($icon)
            <x-ui.icon :name="$icon" />
        @endif
        @if (trim((string) $slot) !== '')<span>{{ $slot }}</span>@endif
        @if ($iconRight && ! $loading)<x-ui.icon :name="$iconRight" />@endif
    </button>
@endif
