@props(['quantity' => 0, 'threshold' => 0])

@php
    $qty = (int) $quantity;
    $threshold = (int) $threshold;
    $max = max($threshold * 2, $qty, 1);
    $tone = $qty <= 0 ? 'danger' : ($qty <= $threshold ? 'warning' : 'success');
    $label = $qty <= 0 ? 'Rupture de stock' : ($qty <= $threshold ? 'Sous le seuil' : 'Stock suffisant');
@endphp

<div {{ $attributes->class(['space-y-1']) }}>
    <div class="flex items-center justify-between gap-2 text-[12.5px]">
        <span class="font-medium tabular-nums text-ink">{{ $qty }} en stock</span>
        <x-ui.badge :tone="$tone" :dot="true">{{ $label }}</x-ui.badge>
    </div>
    <div class="h-2 w-full overflow-hidden rounded-pill bg-hover">
        <div class="h-full rounded-pill {{ $tone === 'danger' ? 'bg-danger-solid' : ($tone === 'warning' ? 'bg-warning-solid' : 'bg-success-solid') }}"
             style="width: {{ max(3, round($qty / $max * 100)) }}%"></div>
    </div>
    <p class="text-[11.5px] text-ink-3">Seuil d'alerte : {{ $threshold }}</p>
</div>
