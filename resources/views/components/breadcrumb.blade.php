@props(['items' => []])

<nav aria-label="Fil d'Ariane" {{ $attributes->merge(['class' => 'flex items-center gap-1.5 text-sm']) }}>
    @foreach ($items as $i => $item)
        @if (! $loop->last && ($item['url'] ?? null))
            <a href="{{ $item['url'] }}" class="text-content-muted hover:text-content-secondary transition">{{ $item['label'] }}</a>
        @else
            <span @class(['text-content-muted' => ! $loop->last, 'text-content font-medium' => $loop->last])>{{ $item['label'] }}</span>
        @endif
        @unless ($loop->last)
            <x-icon name="chevron-right" class="w-4 h-4 text-content-muted shrink-0" />
        @endunless
    @endforeach
</nav>
