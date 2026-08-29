{{--
    Mobile navigation: header + drawer, not the desktop sidebar (per the
    brief - a shrunk desktop sidebar doesn't work well touch-first). Opened
    via mobileMenuOpen, a piece of state on the outer shell in app.blade.php.
--}}
<div x-show="mobileMenuOpen" x-cloak class="lg:hidden fixed inset-0 z-40">
    <div
        x-show="mobileMenuOpen"
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-content/60"
        @click="mobileMenuOpen = false"
    ></div>

    <div
        x-show="mobileMenuOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="relative flex flex-col h-full w-72 max-w-[85vw] bg-surface shadow-soft"
        x-data="{ collapsed: false }"
    >
        <div class="flex items-center gap-2 px-4 py-5">
            <x-brand-logo variant="full" class="h-7 w-auto" />
            <button @click="mobileMenuOpen = false" class="ml-auto p-2 rounded-ui-sm text-content-secondary hover:bg-surface-elevated" aria-label="Fermer le menu">
                <x-icon name="x-mark" class="w-5 h-5" />
            </button>
        </div>

        @include('layouts.partials.sidebar-nav')
    </div>
</div>
