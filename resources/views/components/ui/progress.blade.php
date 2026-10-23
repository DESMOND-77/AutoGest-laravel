@props(['value' => 0, 'max' => 100, 'label' => null, 'variant' => 'bar', 'tone' => 'accent'])

@php
    $max = max(1, (float) $max);
    $value = max(0, min((float) $value, $max));
    $percent = round($value / $max * 100);
    $tones = ['accent' => 'bg-accent', 'success' => 'bg-success-solid', 'warning' => 'bg-warning-solid', 'danger' => 'bg-danger-solid'];
@endphp

@if ($variant === 'ring')
    <div {{ $attributes->class(['relative inline-flex items-center justify-center']) }} role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="{{ $max }}" @if ($label) aria-label="{{ $label }}" @endif>
        <svg viewBox="0 0 36 36" class="w-14 h-14 -rotate-90" aria-hidden="true">
            <circle cx="18" cy="18" r="15.5" fill="none" stroke="var(--line-subtle)" stroke-width="3.5" />
            <circle cx="18" cy="18" r="15.5" fill="none" stroke="var(--accent)" stroke-width="3.5" stroke-linecap="round"
                stroke-dasharray="{{ $percent * 0.974 }} 100" />
        </svg>
        <span class="absolute text-xs font-semibold text-ink">{{ $percent }}%</span>
    </div>
@else
    <div {{ $attributes->class(['w-full']) }} role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="{{ $max }}" @if ($label) aria-label="{{ $label }}" @endif>
        <div class="h-2 w-full overflow-hidden rounded-pill bg-hover">
            <div class="h-full rounded-pill {{ $tones[$tone] ?? $tones['accent'] }}" style="width: {{ $percent }}%"></div>
        </div>
        @if ($label)
            <p class="mt-1 text-caption text-ink-2">{{ $label }} · {{ $percent }}%</p>
        @endif
    </div>
@endif
