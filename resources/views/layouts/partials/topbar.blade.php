@php
    $user = auth()->user();
@endphp

<header class="sticky top-0 z-20 flex items-center gap-3 px-4 sm:px-6 py-4">
    <button
        @click="mobileMenuOpen = true"
        class="lg:hidden shrink-0 rounded-ui-md bg-surface p-2.5 text-content-secondary shadow-soft-sm hover:text-content"
        aria-label="Ouvrir le menu"
    >
        <x-icon name="bars" class="w-5 h-5" />
    </button>

    <div class="min-w-0 flex-1">
        @isset($header)
            <div class="text-base sm:text-lg font-semibold text-content truncate">{{ $header }}</div>
        @endisset

        @if ($user?->structure)
            <p class="text-xs text-content-secondary truncate">{{ $user->structure->name }}</p>
        @elseif ($user?->hasRole('superadmin'))
            <p class="text-xs text-content-secondary truncate">Administration SaaS</p>
        @endif
    </div>

    <div class="flex items-center gap-2 shrink-0" x-data="{
            notifOpen: false,
            unread: 0,
            items: [],
            async load() {
                const res = await fetch('{{ route('notifications.index') }}');
                const data = await res.json();
                this.unread = data.unread_count;
                this.items = data.notifications;
            },
            async markRead() {
                await fetch('{{ route('notifications.read') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                });
                this.unread = 0;
                this.items.forEach(i => i.read = true);
            },
         }" x-init="load(); setInterval(load, 60000)">
        <x-theme-toggle />

        <div class="relative">
            <button
                @click="notifOpen = !notifOpen; if (notifOpen) markRead();"
                class="relative rounded-ui-md bg-surface p-2.5 text-content-secondary shadow-soft-sm hover:text-content transition"
                aria-label="Notifications"
            >
                <x-icon name="bell" class="w-5 h-5" />
                <span x-show="unread > 0" x-cloak x-text="unread" class="absolute -top-1 -right-1 min-w-[1rem] h-4 px-1 rounded-full bg-danger text-white text-[10px] leading-4 text-center"></span>
            </button>
            <div
                x-show="notifOpen"
                @click.outside="notifOpen = false"
                x-cloak
                x-transition
                class="absolute right-0 mt-2 w-80 rounded-ui-md bg-surface shadow-soft py-1 z-50 max-h-96 overflow-y-auto"
            >
                <template x-if="items.length === 0">
                    <div class="px-4 py-3 text-sm text-content-muted">Aucune notification.</div>
                </template>
                <template x-for="item in items" :key="item.id">
                    <a :href="item.link || '#'" class="block px-4 py-2 text-sm border-b border-border/60 last:border-b-0 hover:bg-surface-elevated">
                        <div class="font-medium text-content" x-text="item.title"></div>
                        <div class="text-content-secondary" x-text="item.message"></div>
                        <div class="text-xs text-content-muted" x-text="item.created_at"></div>
                    </a>
                </template>
            </div>
        </div>

        <div class="relative" x-data="{ profileOpen: false }">
            <button
                @click="profileOpen = !profileOpen"
                class="flex items-center gap-2 rounded-ui-md bg-surface pl-1.5 pr-2.5 py-1.5 shadow-soft-sm hover:shadow-soft transition"
            >
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-primary text-primary-content text-xs font-semibold">
                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                </span>
                <span class="hidden sm:block text-sm font-medium text-content max-w-[9rem] truncate">{{ $user->name }}</span>
                <x-icon name="chevron-down" class="w-4 h-4 text-content-muted hidden sm:block" />
            </button>

            <div
                x-show="profileOpen"
                @click.outside="profileOpen = false"
                x-cloak
                x-transition
                class="absolute right-0 mt-2 w-48 rounded-ui-md bg-surface shadow-soft py-1 z-50"
            >
                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-content-secondary hover:bg-surface-elevated hover:text-content">
                    Profil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-content-secondary hover:bg-surface-elevated hover:text-content">
                        Se déconnecter
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
