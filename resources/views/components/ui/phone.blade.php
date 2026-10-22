@if ($value)
    @if ($link && $href())
        <a href="{{ $href() }}" {{ $attributes->class(['whitespace-nowrap hover:text-accent']) }}>{{ $display() }}</a>
    @else
        <span {{ $attributes->class(['whitespace-nowrap']) }}>{{ $display() }}</span>
    @endif
@else
    <span {{ $attributes->class(['text-ink-3']) }}>—</span>
@endif
