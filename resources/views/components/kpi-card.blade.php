@props(['icon', 'label', 'value', 'href' => null, 'trend' => null, 'trendUp' => true])

@php
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'block bg-surface rounded-ui-lg shadow-soft p-5'.($href ? ' hover:shadow-soft-hover transition' : '')]) }}>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm text-content-secondary truncate">{{ $label }}</p>
            <p class="mt-2 text-2xl font-bold text-content">{{ $value }}</p>

            @if ($trend !== null)
                <p @class([
                    'mt-1 inline-flex items-center gap-1 text-xs font-medium',
                    'text-success' => $trendUp,
                    'text-danger' => ! $trendUp,
                ])>
                    <x-icon name="arrow-trending-up" class="w-3.5 h-3.5" :class="! $trendUp ? '-scale-y-100' : ''" />
                    {{ $trend }}
                </p>
            @endif
        </div>

        <span class="shrink-0 flex h-11 w-11 items-center justify-center rounded-ui-md bg-primary/10 text-primary">
            <x-icon :name="$icon" class="w-5 h-5" />
        </span>
    </div>
</{{ $tag }}>
