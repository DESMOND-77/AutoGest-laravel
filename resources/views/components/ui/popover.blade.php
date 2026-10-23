@props(['align' => 'right', 'width' => 'w-80', 'label' => 'Ouvrir le panneau'])

<div x-data="{ open: false }" class="relative" @keydown.escape.prevent.stop="open = false" @click.outside="open = false">
    <button type="button" @click="open = !open" :aria-expanded="open" aria-haspopup="dialog" aria-label="{{ $label }}" x-ref="anchor"
        {{ $attributes->class(['inline-flex items-center justify-center rounded-full text-ink-2 hover:bg-hover hover:text-ink focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]']) }}>
        {{ $trigger }}
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.150ms
        x-anchor.{{ $align === 'left' ? 'bottom-start' : 'bottom-end' }}.offset.8="$refs.anchor"
        role="dialog"
        class="z-50 rounded-lg border border-line bg-solid p-4 shadow-lg {{ $width }}"
    >
        {{ $slot }}
    </div>
</div>
