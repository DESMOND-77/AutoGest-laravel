@props(['title' => null])

@php
    $user = auth()->user();
    $unread = $user?->unreadNotifications?->count() ?? 0;
    $recent = $user?->unreadNotifications?->take(5) ?? collect();
    $greetingRole = match (true) {
        (bool) $user?->hasRole('superadmin') => 'Super-administrateur',
        (bool) $user?->hasRole('admin') => 'Administrateur',
        (bool) $user?->hasRole('moniteur') => 'Moniteur',
        default => 'Élève',
    };
@endphp

<header class="topbar sticky top-0 z-30 flex h-[var(--topbar-h)] items-center gap-2 border-b border-line/70 bg-[var(--canvas-base)]/85 px-3 backdrop-blur-sm sm:px-5">
    <button
        type="button"
        class="grid size-10 shrink-0 place-items-center rounded-sm text-ink-2 hover:bg-hover hover:text-ink focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)] lg:hidden"
        x-on:click="$store.shell.mobileOpen = true"
        aria-label="Ouvrir la navigation"
    >
        <x-ui.icon name="menu" />
    </button>

    <div class="min-w-0 flex-1">
        <p class="hidden text-[11px] font-semibold uppercase tracking-[0.08em] text-ink-3 sm:block">{{ $greetingRole }}</p>
        <p class="truncate text-[14px] font-semibold text-ink sm:text-[15px]">
            {{ $title ?? ($user?->structure?->name ?? 'Auto-GestBoard') }}
        </p>
    </div>

    <button
        type="button"
        class="hidden h-9 items-center gap-2 rounded-pill border border-line bg-[var(--surface-inset)] px-3 text-[13px] text-ink-3 hover:text-ink focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)] md:inline-flex"
        x-on:click="window.dispatchEvent(new CustomEvent('palette:open'))"
        aria-label="Rechercher (Ctrl K)"
    >
        <x-ui.icon name="search" class="w-4 h-4" />
        <span class="hidden lg:inline">Rechercher…</span>
        <x-ui.kbd class="ml-2 hidden lg:inline-flex">Ctrl K</x-ui.kbd>
    </button>

    @if (count($createActions ?? []))
        <div x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false" class="relative">
            <button
                type="button"
                x-ref="createTrigger"
                class="inline-flex h-9 items-center gap-1.5 rounded-pill bg-accent px-3 text-[13px] font-semibold text-on-accent hover:bg-accent-hover focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]"
                x-on:click="open = !open"
                :aria-expanded="open.toString()"
                aria-haspopup="menu"
            >
                <x-ui.icon name="plus" class="w-4 h-4" />
                <span class="hidden sm:inline">Nouveau</span>
            </button>
            <div
                x-cloak
                x-show="open"
                x-transition.duration.150ms
                x-anchor.bottom-end.offset.8="$refs.createTrigger"
                class="material-solid z-50 w-56 rounded-md p-1"
                role="menu"
            >
                @foreach ($createActions as $action)
                    <a href="{{ route($action['route']) }}" role="menuitem" class="flex items-center gap-2 rounded-sm px-2.5 py-2 text-[13.5px] text-ink hover:bg-hover focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">
                        <x-ui.icon :name="$action['icon']" class="w-4 h-4 text-ink-3" />
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="flex items-center gap-1">
        {{-- Notifications : lues côté serveur, marquage via le seul endpoint existant --}}
        <div
            x-data="{ open: false }"
            x-on:click.outside="open = false"
            x-on:keydown.escape.window="open = false"
            class="relative"
        >
            <button
                type="button"
                x-ref="notifTrigger"
                class="relative grid size-10 place-items-center rounded-sm text-ink-2 hover:bg-hover hover:text-ink focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]"
                x-on:click="open = !open"
                :aria-expanded="open.toString()"
                aria-haspopup="true"
                :aria-label="@js('Notifications'.($unread > 0 ? ' — '.$unread.' non lues' : ''))"
            >
                <x-ui.icon name="bell" />
                @if ($unread > 0)
                    <span class="absolute right-1.5 top-1.5 grid min-w-[16px] place-items-center rounded-full bg-danger-solid px-1 text-[10px] font-bold leading-4 text-on-solid">{{ $unread > 9 ? '9+' : $unread }}</span>
                @endif
            </button>

            <div
                x-cloak
                x-show="open"
                x-transition.duration.200ms
                x-anchor.bottom-end.offset.8="$refs.notifTrigger"
                class="material-solid z-50 w-[min(92vw,22rem)] overflow-hidden rounded-lg p-0"
                role="dialog"
                aria-label="Notifications"
            >
                <div class="flex items-center justify-between gap-2 border-b border-line px-4 py-3">
                    <p class="text-sm font-semibold text-ink">Notifications</p>
                    @if ($unread > 0)
                        <form method="POST" action="{{ route('notifications.read') }}">
                            @csrf
                            <x-ui.button type="submit" size="sm" variant="ghost">Tout marquer lu</x-ui.button>
                        </form>
                    @endif
                </div>
                @if ($recent->isEmpty())
                    <p class="px-4 py-6 text-center text-[13px] text-ink-3">Aucune nouvelle notification.</p>
                @else
                    <ul class="max-h-80 divide-y divide-line overflow-y-auto">
                        @foreach ($recent as $notification)
                            <li class="px-4 py-3">
                                <p class="text-[13px] font-medium text-ink">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                <p class="mt-0.5 text-[12.5px] text-ink-2">{{ $notification->data['message'] ?? '' }}</p>
                                <p class="mt-1 text-[11.5px] text-ink-3"><x-ui.date :value="$notification->created_at" format="relative" /></p>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <p class="border-t border-line px-4 py-2.5 text-[11.5px] text-ink-3">
                    Les notifications sont conservées dans votre espace. Le centre de notifications complet n’est pas encore disponible.
                </p>
            </div>
        </div>

        <x-layout.appearance-popover />

        <x-layout.user-menu />
    </div>
</header>
