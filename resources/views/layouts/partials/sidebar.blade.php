{{--
    Desktop floating soft-UI sidebar. Hidden below lg; see
    mobile-drawer.blade.php for small screens. `collapsed` lives on the
    root shell in app.blade.php (a sibling <div> needs it too, to offset
    the main content - Alpine scope only cascades to descendants).
--}}
<aside
    :class="collapsed ? 'lg:w-20' : 'lg:w-64'"
    class="hidden lg:flex lg:flex-col fixed inset-y-3 left-3 z-30 rounded-ui-xl bg-surface shadow-soft transition-[width] duration-200"
>
    <div class="flex items-center gap-2 px-4 py-5" :class="collapsed && 'justify-center px-2'">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 min-w-0">
            <x-brand-logo variant="full" x-show="!collapsed" x-cloak class="h-7 w-auto" />
            <x-brand-logo variant="icon" x-show="collapsed" x-cloak class="h-8 w-8" />
        </a>
    </div>

    @include('layouts.partials.sidebar-nav')

    <div class="p-3">
        <button
            @click="collapsed = !collapsed"
            class="flex w-full items-center justify-center gap-2 rounded-ui-md py-2 text-content-secondary hover:bg-surface-elevated hover:text-content transition"
            :aria-label="collapsed ? 'Déplier le menu' : 'Replier le menu'"
        >
            <x-icon name="chevron-left" class="w-4 h-4 transition-transform" x-bind:class="collapsed ? 'rotate-180' : ''" />
            <span x-show="!collapsed" x-cloak class="text-xs font-medium">Replier</span>
        </button>
    </div>
</aside>
