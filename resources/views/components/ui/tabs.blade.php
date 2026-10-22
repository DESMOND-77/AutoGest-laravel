@props(['items' => [], 'current' => null, 'variant' => 'underline', 'label' => 'Navigation des onglets'])

@php
    $isActive = fn ($item) => ($item['active'] ?? false) || (($item['key'] ?? null) !== null && ($item['key'] ?? null) === $current);
@endphp

<nav aria-label="{{ $label }}" {{ $attributes->class([$variant === 'underline' ? 'border-b border-line' : '']) }}>
    <ul role="tablist" class="no-scrollbar flex items-center gap-1 overflow-x-auto {{ $variant === 'pill' ? 'rounded-pill bg-hover p-1' : '' }}">
        @foreach ($items as $item)
            <li role="presentation">
                <a
                    href="{{ $item['href'] ?? '#' }}"
                    role="tab"
                    aria-selected="{{ $isActive($item) ? 'true' : 'false' }}"
                    @if ($isActive($item)) aria-current="page" @endif
                    class="inline-flex items-center gap-2 whitespace-nowrap text-sm font-medium transition-colors focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]
                        {{ $variant === 'pill'
                            ? 'rounded-pill px-3.5 py-1.5 ' . ($isActive($item) ? 'bg-solid text-ink shadow-sm' : 'text-ink-2 hover:text-ink')
                            : 'border-b-2 px-3 py-2.5 -mb-px ' . ($isActive($item) ? 'border-accent text-accent' : 'border-transparent text-ink-2 hover:text-ink') }}"
                >
                    {{ $item['label'] }}
                    @if (isset($item['count']))
                        <span class="rounded-pill px-1.5 text-[11px] {{ $isActive($item) ? 'bg-accent-soft text-accent' : 'bg-hover text-ink-2' }}">{{ $item['count'] }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
</nav>
