@props(['text', 'placement' => 'top'])

<span x-data="{ show: false }" class="relative inline-flex" @mouseenter="show = true" @mouseleave="show = false" @focusin="show = true" @focusout="show = false">
    {{ $slot }}
    <span
        x-show="show"
        x-cloak
        x-transition.opacity.duration.120ms
        role="tooltip"
        class="absolute z-50 w-max max-w-56 rounded-sm border border-line bg-solid px-2.5 py-1.5 text-xs font-medium text-ink shadow-md
            {{ $placement === 'bottom' ? 'top-full mt-2' : ($placement === 'left' ? 'right-full mr-2 top-1/2 -translate-y-1/2' : ($placement === 'right' ? 'left-full ml-2 top-1/2 -translate-y-1/2' : 'bottom-full mb-2')) }}
            {{ in_array($placement, ['top', 'bottom']) ? 'left-1/2 -translate-x-1/2' : '' }}"
    >{{ $text }}</span>
</span>
