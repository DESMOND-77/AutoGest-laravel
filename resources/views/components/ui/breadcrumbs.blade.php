@props(['items' => []])

<nav aria-label="Fil d'Ariane" {{ $attributes }}>
    <ol class="flex flex-wrap items-center gap-1.5 text-[13px] text-ink-2">
        @foreach ($items as $item)
            <li class="flex items-center gap-1.5">
                @if (! $loop->first)
                    <x-ui.icon name="chevron-right" class="w-3.5 h-3.5 text-ink-3" />
                @endif
                @if (! empty($item['href']) && ! $loop->last)
                    <a href="{{ $item['href'] }}" class="rounded-xs hover:text-accent focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">{{ $item['label'] }}</a>
                @else
                    <span @if ($loop->last) aria-current="page" class="text-ink" @endif>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
