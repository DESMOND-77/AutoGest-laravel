@props(['data' => [], 'label' => 'Tendance', 'variant' => 'area'])

@php
    $values = collect($data)->map(fn ($v) => (float) $v)->values()->all();
    $count = count($values);
    $max = $count ? max($values) : 1;
    $min = $count ? min($values) : 0;
    $span = ($max - $min) ?: 1;
    $points = [];
    foreach ($values as $i => $v) {
        $x = $count > 1 ? ($i / ($count - 1)) * 100 : 50;
        $y = 30 - (($v - $min) / $span) * 26 - 2;
        $points[] = round($x, 2).','.round($y, 2);
    }
    $line = implode(' ', $points);
    $area = $count ? "0,30 {$line} 100,30" : '';
    $summary = $count ? 'De '.$min.' à '.$max : 'Aucune donnée';
@endphp

<svg {{ $attributes->class(['overflow-visible']) }} viewBox="0 0 100 30" preserveAspectRatio="none" role="img" aria-label="{{ $label }} : {{ $summary }}">
    @if ($count > 1)
        @if ($variant === 'area')
            <polygon points="{{ $area }}" fill="var(--accent-soft)" />
        @endif
        <polyline points="{{ $line }}" fill="none" stroke="var(--accent)" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
    @else
        <line x1="0" y1="20" x2="100" y2="20" stroke="var(--line-subtle)" stroke-width="1.5" stroke-dasharray="4 4" />
    @endif
</svg>
