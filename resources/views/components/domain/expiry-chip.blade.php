@props(['date' => null, 'label' => 'Échéance', 'icon' => 'calendar-clock'])

@php
    $target = $date ? \Illuminate\Support\Carbon::parse($date)->startOfDay() : null;
    $days = $target ? (int) now()->startOfDay()->diffInDays($target, false) : null;

    if ($days === null) {
        $tone = 'neutral';
        $text = 'Non renseignée';
    } elseif ($days < 0) {
        $tone = 'danger';
        $text = 'Expirée depuis '.abs($days).' j';
    } elseif ($days <= 7) {
        $tone = 'danger';
        $text = 'Dans '.$days.' j';
    } elseif ($days <= 30) {
        $tone = 'warning';
        $text = 'Dans '.$days.' j';
    } else {
        $tone = 'neutral';
        $text = 'Dans '.$days.' j';
    }
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 text-[12px]']) }}>
    <x-ui.icon :name="$icon" class="w-3.5 h-3.5 text-ink-3" />
    <span class="text-ink-3">{{ $label }}</span>
    <x-ui.badge :tone="$tone" :icon="$tone === 'danger' ? 'circle-alert' : null">{{ $text }}</x-ui.badge>
</span>
