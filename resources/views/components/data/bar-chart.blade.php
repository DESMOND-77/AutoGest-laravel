@props(['series' => [], 'label' => 'Comparaison', 'height' => 'h-40'])

@php
    $max = collect($series)->max('value') ?: 1;
@endphp

<div {{ $attributes->class(['flex items-end gap-2 '.$height]) }} role="img" aria-label="{{ $label }}">
    @foreach ($series as $item)
        <div class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1.5">
            <span class="text-[11px] font-semibold text-ink-2">{{ $item['value'] ?? 0 }}</span>
            <div class="w-full rounded-t-sm bg-accent-soft" style="height: {{ max(2, round(($item['value'] ?? 0) / $max * 100)) }}%">
                <div class="h-full w-full rounded-t-sm bg-accent opacity-80"></div>
            </div>
            <span class="w-full truncate text-center text-[11px] text-ink-3">{{ $item['label'] ?? '' }}</span>
        </div>
    @endforeach
</div>
