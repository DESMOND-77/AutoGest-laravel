@props(['text'])

<span x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false"
      {{ $attributes->merge(['class' => 'relative inline-flex']) }}>
    {{ $slot }}
    <span x-show="open" x-cloak
          class="pointer-events-none absolute bottom-full left-1/2 mb-2 -translate-x-1/2 whitespace-nowrap rounded-ui-sm bg-content px-2 py-1 text-xs font-medium text-background">
        {{ $text }}
    </span>
</span>
