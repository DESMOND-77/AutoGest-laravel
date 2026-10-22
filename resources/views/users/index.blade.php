<x-app-layout>
    <x-slot name="header">Comptes utilisateurs</x-slot>

    <div class="py-6 space-y-4 max-w-5xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert variant="danger">{{ $errors->first() }}</x-alert>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach (['admin' => 'Administrateurs', 'moniteur' => 'Moniteurs', 'eleve' => 'Élèves'] as $role => $label)
                <a
                    href="{{ route('settings.users.index', ['role' => $role]) }}"
                    @class([
                        'block rounded-ui-md p-4 shadow-soft-sm transition',
                        'bg-primary text-primary-content' => $roleFilter === $role,
                        'bg-surface hover:shadow-soft' => $roleFilter !== $role,
                    ])
                >
                    <p class="text-xs uppercase tracking-wide opacity-80">{{ $label }}</p>
                    <p class="text-2xl font-semibold">{{ $roleCounts[$role] ?? 0 }}</p>
                </a>
            @endforeach
        </div>

        @if ($roleFilter)
            <a href="{{ route('settings.users.index') }}" class="text-xs text-primary hover:underline">Voir tous les rôles</a>
        @endif

        <x-card>
            <h2 class="text-sm font-semibold text-content mb-3">Créer un compte administrateur</h2>
            <form method="POST" action="{{ route('settings.users.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @csrf
                <div>
                    <x-input-label for="name" value="Nom complet" />
                    <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="email" value="E-mail" />
                    <x-text-input id="email" type="email" name="email" class="block mt-1 w-full" :value="old('email')" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div class="sm:col-span-2">
                    <x-primary-button>Créer le compte</x-primary-button>
                </div>
            </form>
            <p class="text-xs text-content-muted mt-3">
                Le nouveau compte reçoit un e-mail avec un lien pour définir son mot de passe — aucun mot de passe n'est choisi ici.
                Les comptes élève se créent depuis la fiche d'un élève, et les comptes moniteur depuis <a href="{{ route('instructors.index') }}" class="text-primary hover:underline">Moniteurs</a>.
            </p>
        </x-card>

        <x-card :padded="false">
            <ul class="divide-y divide-border/60">
                @forelse ($users as $user)
                    <li class="px-5 py-3 flex items-center justify-between gap-3 text-sm">
                        <div class="min-w-0">
                            <p class="text-content font-medium">{{ $user->name }}</p>
                            <p class="text-content-muted text-xs">{{ $user->email }} · ID {{ $user->id }}</p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            @foreach ($user->roles as $role)
                                <x-badge variant="info">{{ $role->name }}</x-badge>
                            @endforeach
                            <x-badge :variant="$user->is_active ? 'success' : 'neutral'">
                                {{ $user->is_active ? 'Actif' : 'Désactivé' }}
                            </x-badge>
                            <form method="POST" action="{{ route('settings.users.reset-password', $user) }}">
                                @csrf
                                <button type="submit" class="text-xs text-primary hover:underline">Réinitialiser le mot de passe</button>
                            </form>
                            @if ($user->is_active)
                                @unless ($user->is(auth()->user()))
                                    <form method="POST" action="{{ route('settings.users.deactivate', $user) }}" onsubmit="return confirm('Désactiver ce compte ?');">
                                        @csrf
                                        <button type="submit" class="text-xs text-danger hover:underline">Désactiver</button>
                                    </form>
                                @endunless
                            @else
                                <form method="POST" action="{{ route('settings.users.reactivate', $user) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-success hover:underline">Réactiver</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-content-muted text-sm">Aucun compte pour le moment.</li>
                @endforelse
            </ul>
            <div class="px-5 py-3 border-t border-border/60">
                {{ $users->links() }}
            </div>
        </x-card>
    </div>
</x-app-layout>
