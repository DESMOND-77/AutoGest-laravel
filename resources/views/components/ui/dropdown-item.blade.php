@props(['href' => null, 'icon' => null, 'danger' => false, 'type' => 'button'])

@php
    $base = 'flex w-full items-center gap-2.5 rounded-sm px-2.5 py-2 text-left text-sm transition-colors focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]';
    $tone = $danger ? 'text-danger hover:bg-danger-soft' : 'text-ink hover:bg-hover';
@endphp

@if ($href)
    <a href="{{ $href }}" role="menuitem" {{ $attributes->class([$base, $tone]) }}>
        @if ($icon)<x-ui.icon :name="$icon" class="w-4 h-4" />@endif
        <span class="flex-1">{{ $slot }}</span>
    </a>
@else
    <button type="{{ $type }}" role="menuitem" {{ $attributes->class([$base, $tone]) }}>
        @if ($icon)<x-ui.icon :name="$icon" class="w-4 h-4" />@endif
        <span class="flex-1">{{ $slot }}</span>
    </button>
@endif
