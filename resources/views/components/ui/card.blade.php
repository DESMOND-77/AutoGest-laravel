@props(['variant' => 'panel', 'title' => null, 'description' => null, 'padding' => 'p-5 max-sm:p-4'])

@php
    $base = match ($variant) {
        'solid' => 'material-solid',
        'flat' => 'bg-hover/40 border border-line rounded-lg',
        default => 'material-panel',
    };
@endphp

<section {{ $attributes->class([$base]) }}>
    @if ($title || isset($actions) || $description)
        <header class="flex flex-wrap items-start justify-between gap-3 border-b border-line px-5 py-3.5 max-sm:px-4">
            <div class="min-w-0">
                @if ($title)
                    <h2 class="text-section-title text-ink">{{ $title }}</h2>
                @endif
                @if ($description)
                    <p class="mt-0.5 text-[13px] text-ink-2">{{ $description }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif
    <div class="{{ $padding }}">{{ $slot }}</div>
    @isset($footer)
        <footer class="border-t border-line px-5 py-3 text-sm text-ink-2 max-sm:px-4">{{ $footer }}</footer>
    @endisset
</section>
