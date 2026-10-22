@props(['keys' => 'K'])

<kbd
    x-data="{ mac: /mac|iphone|ipad/i.test(navigator.platform || navigator.userAgent) }"
    {{ $attributes->class(['inline-flex items-center gap-1 rounded-xs border border-line bg-inset px-1.5 py-0.5 font-mono text-[11px] text-ink-2']) }}
>
    <span x-show="mac" aria-hidden="true">⌘</span>
    <span x-show="!mac" aria-hidden="true">Ctrl</span>
    <span aria-hidden="true">{{ $keys }}</span>
</kbd>
