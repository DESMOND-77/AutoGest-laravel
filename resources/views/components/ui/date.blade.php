@if ($date())
    <time
        {{ $attributes->class(['whitespace-nowrap']) }}
        datetime="{{ $iso() }}"
        @if ($relative) title="{{ $absolute() }}" @endif
    >{{ $relative ? $date()->diffForHumans() : $display() }}</time>
@else
    <span {{ $attributes->class(['text-ink-3']) }}>—</span>
@endif
