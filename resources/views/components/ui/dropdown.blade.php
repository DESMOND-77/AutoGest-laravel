@props(['align' => 'right', 'width' => 'w-56'])

<div x-data="{ open: false }" class="relative inline-flex" @keydown.escape.prevent.stop="open = false" @click.outside="open = false">
    <div x-ref="anchor" @click="open = !open" :aria-expanded="open" aria-haspopup="menu" class="inline-flex">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity.duration.120ms
        x-anchor.{{ $align === 'left' ? 'bottom-start' : 'bottom-end' }}.offset.8="$refs.anchor"
        role="menu"
        class="z-50 rounded-md border border-line bg-solid p-1.5 shadow-lg {{ $width }}"
        @keydown.arrow-down.prevent="$el.querySelector('[role=menuitem]')?.focus()"
    >
        {{ $slot }}
    </div>
</div>
