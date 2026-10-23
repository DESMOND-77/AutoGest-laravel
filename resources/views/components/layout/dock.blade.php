@php
    $dockItems = collect($navGroups ?? [])
        ->flatMap(fn ($group) => $group['items'])
        ->take(4)
        ->values();
@endphp

{{-- Cible tactile principale sur mobile : 4 destinations + « Plus » --}}
<nav
    class="dock fixed inset-x-0 bottom-0 z-30 border-t border-line/70 bg-[var(--surface-panel)]/95 pb-[env(safe-area-inset-bottom)] backdrop-blur-sm lg:hidden"
    aria-label="Navigation mobile"
>
    <div class="mx-auto flex max-w-md items-stretch justify-between px-1">
        @foreach ($dockItems as $item)
            @php $active = request()->routeIs($item['match'] ?? $item['route']); @endphp
            <a
                href="{{ route($item['route']) }}"
                @if ($active) aria-current="page" @endif
                class="flex min-h-[var(--dock-h)] flex-1 flex-col items-center justify-center gap-0.5 rounded-sm px-1 text-[11px] font-medium focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)] {{ $active ? 'text-accent' : 'text-ink-3' }}"
            >
                <x-ui.icon :name="$item['icon'] ?? 'circle'" class="w-[22px] h-[22px]" />
                <span class="max-w-full truncate">{{ \Illuminate\Support\Str::limit($item['label'], 12) }}</span>
            </a>
        @endforeach

        <button
            type="button"
            x-on:click="$store.shell.mobileOpen = true"
            class="flex min-h-[var(--dock-h)] flex-1 flex-col items-center justify-center gap-0.5 rounded-sm px-1 text-[11px] font-medium text-ink-3 focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]"
            aria-label="Ouvrir toutes les sections"
        >
            <x-ui.icon name="menu" class="w-[22px] h-[22px]" />
            <span>Plus</span>
        </button>
    </div>
</nav>
