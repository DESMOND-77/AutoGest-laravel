@php
    $user = auth()->user();
    $roleLabel = match (true) {
        (bool) $user?->hasRole('superadmin') => 'Super-administrateur',
        (bool) $user?->hasRole('admin') => 'Administrateur',
        (bool) $user?->hasRole('moniteur') => 'Moniteur',
        (bool) $user?->hasRole('eleve') => 'Élève',
        default => 'Utilisateur',
    };
@endphp

@if ($user)
    <div x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape.window="open = false" class="relative">
        <button
            type="button"
            x-ref="userTrigger"
            class="grid size-10 place-items-center rounded-full focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]"
            x-on:click="open = !open"
            :aria-expanded="open.toString()"
            aria-haspopup="menu"
            aria-label="Mon compte"
        >
            <x-ui.avatar :name="$user->name" size="sm" />
        </button>

        <div
            x-cloak
            x-show="open"
            x-transition.duration.150ms
            x-anchor.bottom-end.offset.8="$refs.userTrigger"
            class="material-solid z-50 w-64 rounded-lg p-1.5"
            role="menu"
        >
            <div class="flex items-center gap-2.5 border-b border-line px-2.5 pb-2.5 pt-1.5">
                <x-ui.avatar :name="$user->name" size="md" />
                <div class="min-w-0">
                    <p class="truncate text-[13.5px] font-semibold text-ink">{{ $user->name }}</p>
                    <p class="truncate text-[11.5px] text-ink-3">{{ $user->email }}</p>
                    <x-ui.badge tone="neutral" class="mt-1">{{ $roleLabel }}</x-ui.badge>
                </div>
            </div>

            <a href="{{ route('profile.edit') }}" role="menuitem" class="mt-1 flex items-center gap-2 rounded-sm px-2.5 py-2 text-[13.5px] text-ink hover:bg-hover focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">
                <x-ui.icon name="user" class="w-4 h-4 text-ink-3" /> Mon profil
            </a>

            <div class="mt-1 border-t border-line pt-1">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" role="menuitem" class="flex w-full items-center gap-2 rounded-sm px-2.5 py-2 text-left text-[13.5px] text-ink hover:bg-hover focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">
                        <x-ui.icon name="logout" class="w-4 h-4 text-ink-3" /> Se déconnecter
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif
