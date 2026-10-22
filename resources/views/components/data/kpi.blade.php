@props([
    'label',
    'value' => null,
    'delta' => null,
    'deltaLabel' => null,
    'trend' => 'up',
    'icon' => null,
    'href' => null,
    'spark' => null,
    'hint' => null,
])

@php
    $trendUp = $trend === 'up';
    $trendTone = $trendUp ? 'text-success' : 'text-danger';
@endphp

<div {{ $attributes->class(['material-panel flex flex-col gap-2 p-5 max-sm:p-4']) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-[13px] font-medium text-ink-2">{{ $label }}</p>
        @if ($icon)
            <span class="grid size-8 place-items-center rounded-full bg-accent-soft text-accent" aria-hidden="true">
                <x-ui.icon :name="$icon" class="w-4 h-4" />
            </span>
        @endif
    </div>

    <p class="text-[28px] font-bold leading-none tracking-tight text-ink">
        @if ($value !== null) {{ $value }} @else {{ $slot }} @endif
    </p>

    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
        @if ($delta !== null)
            <span class="inline-flex items-center gap-1 text-[13px] font-semibold {{ $trendTone }}">
                <x-ui.icon :name="$trendUp ? 'trending-up' : 'trending-down'" class="w-3.5 h-3.5" />
                {{ $delta }}
            </span>
        @endif
        @if ($deltaLabel)
            <span class="text-[12.5px] text-ink-3">{{ $deltaLabel }}</span>
        @endif
    </div>

    @if ($spark)
        <x-data.sparkline :data="$spark" class="mt-1 h-8 w-full" :label="$label" />
    @endif

    @if ($hint)
        <p class="text-caption text-ink-3">{{ $hint }}</p>
    @endif

    @if ($href)
        <a href="{{ $href }}" class="mt-auto inline-flex items-center gap-1 text-[13px] font-medium text-accent hover:underline focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">
            Voir le détail <x-ui.icon name="chevron-right" class="w-3.5 h-3.5" />
        </a>
    @endif
</div>
