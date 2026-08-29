@props(['href', 'active' => false, 'icon' => null, 'badge' => null])

{{--
    Relies on an ancestor <aside x-data="{ collapsed: ... }"> — Blade
    components inline into the same DOM position, so Alpine's `collapsed`
    stays in scope here without needing to thread it through as a prop.
--}}
<a
    href="{{ $href }}"
    @class([
        'group relative flex items-center gap-3 rounded-ui-md px-3 py-2 text-sm transition',
        'bg-surface shadow-inset font-medium text-primary' => $active,
        'text-content-secondary hover:bg-surface-elevated hover:text-content' => ! $active,
    ])
    :class="collapsed && 'justify-center'"
>
    @if ($icon)
        <x-icon :name="$icon" class="w-5 h-5 shrink-0" />
    @endif
    <span x-show="!collapsed" x-cloak>{{ $slot }}</span>

    @if ($badge)
        <span class="absolute top-0.5 right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-danger px-1 text-[10px] font-semibold leading-none text-white">
            {{ $badge }}
        </span>
    @endif

    <span
        x-show="collapsed"
        x-cloak
        class="pointer-events-none absolute left-full ml-2 z-20 hidden whitespace-nowrap rounded-ui-sm bg-content px-2 py-1 text-xs font-medium text-background group-hover:block"
    >
        {{ $slot }}
    </span>
</a>
