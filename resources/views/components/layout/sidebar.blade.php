@props(['groups' => null])

@php
    $groups = $groups ?? ($navGroups ?? []);
    $user = auth()->user();
@endphp

{{-- Fond du tiroir mobile --}}
<div
    x-cloak
    x-show="$store.shell.mobileOpen"
    x-transition.opacity.duration.200ms
    x-on:click="$store.shell.closeMobile()"
    class="fixed inset-0 z-40 bg-[var(--scrim)] lg:hidden"
    aria-hidden="true"
></div>

<aside
    class="sidebar fixed inset-y-0 left-0 z-50 flex w-[var(--sidebar-w)] flex-col border-r border-line/70 bg-[var(--surface-panel)]/92 backdrop-blur-sm transition-transform duration-[var(--dur-2)] lg:translate-x-0 lg:bg-[var(--surface-panel)]/70"
    :class="$store.shell.mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    aria-label="Navigation principale"
>
    <div class="flex h-[var(--topbar-h)] shrink-0 items-center gap-2 border-b border-line/70 px-3">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2.5 rounded-sm px-1 focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">
            <x-brand.logo variant="icon" size="sm" />
            <span class="truncate text-[14px] font-bold tracking-tight text-ink" x-show="!$store.shell.collapsed" x-cloak>Auto-GestBoard</span>
        </a>
        <button
            type="button"
            class="ml-auto hidden size-9 shrink-0 place-items-center rounded-sm text-ink-3 hover:bg-hover hover:text-ink focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)] lg:grid"
            x-on:click="$store.shell.toggle()"
            :aria-expanded="(!$store.shell.collapsed).toString()"
            aria-label="Replier ou déplier la navigation"
        >
            <x-ui.icon name="chevrons-left" x-bind:class="$store.shell.collapsed && 'rotate-180'" class="w-4 h-4 transition-transform duration-[var(--dur-1)]" />
        </button>
    </div>

    <nav class="min-h-0 flex-1 overflow-y-auto px-2 py-3" aria-label="Sections">
        @forelse ($groups as $group)
            <div class="mb-1" x-data="{ open: true }">
                <div class="flex items-center justify-between px-2" x-show="!$store.shell.collapsed" x-cloak>
                    <p class="py-1.5 text-[11px] font-bold uppercase tracking-[0.08em] text-ink-3">{{ $group['label'] }}</p>
                </div>
                <div class="space-y-0.5">
                    @foreach ($group['items'] as $item)
                        @php
                            $active = request()->routeIs($item['match'] ?? $item['route']);
                        @endphp
                        <a
                            href="{{ route($item['route']) }}"
                            @if ($active) aria-current="page" @endif
                            :title="$store.shell.collapsed ? @js($item['label']) : null"
                            class="group flex h-10 items-center gap-2.5 rounded-sm px-2 text-[13.5px] font-medium transition-colors duration-[var(--dur-1)] focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)] {{ $active ? 'bg-accent-soft text-accent' : 'text-ink-2 hover:bg-hover hover:text-ink' }}"
                        >
                            <x-ui.icon :name="$item['icon'] ?? 'circle'" class="w-[18px] h-[18px] shrink-0 {{ $active ? 'text-accent' : 'text-ink-3 group-hover:text-ink'" }}" />
                            <span class="truncate" x-show="!$store.shell.collapsed" x-cloak>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="px-2 py-4 text-[12.5px] text-ink-3">Aucune section disponible pour votre rôle.</p>
        @endforelse
    </nav>

    <div class="shrink-0 border-t border-line/70 p-2">
        @if ($user)
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 rounded-sm p-2 hover:bg-hover focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">
                <x-ui.avatar :name="$user->name" size="sm" />
                <span class="min-w-0" x-show="!$store.shell.collapsed" x-cloak>
                    <span class="block truncate text-[13px] font-medium text-ink">{{ $user->name }}</span>
                    <span class="block truncate text-[11.5px] text-ink-3">{{ $user->structure?->name ?? 'Plateforme' }}</span>
                </span>
            </a>
        @endif
        <p class="px-2 pb-1 pt-1 text-[11px] text-ink-3" x-show="!$store.shell.collapsed" x-cloak>
            Montants en FCFA · {{ now()->format('d/m/Y') }}
        </p>
    </div>
</aside>
