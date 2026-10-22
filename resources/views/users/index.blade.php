@php
    $roles = ['admin' => 'Administrateur', 'moniteur' => 'Moniteur', 'eleve' => 'Élève'];
    $tabs = collect($roles)->map(fn ($label, $key) => [
        'key' => $key, 'label' => $label, 'count' => (int) ($roleCounts[$key] ?? 0),
    ]);
@endphp

<x-app-layout :title="'Utilisateurs'">
    <x-layout.page-header
        title="Utilisateurs"
        description="Les comptes de votre établissement. Les élèves disposent en plus d’une fiche pédagogique."
        :breadcrumbs="[['label' => 'Administration'], ['label' => 'Utilisateurs']]"
    >
        <x-slot:actions>
            @can('create', \App\Models\User::class)
                <x-ui.button icon="user-plus" x-on:click="$dispatch('open-dialog', { name: 'new-user' })">Nouveau compte</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        <x-ui.alert tone="info">
            Les moniteurs se créent depuis <a href="{{ route('instructors.index') }}" class="font-medium underline">Moniteurs</a> et les élèves depuis leur fiche. Ce formulaire crée uniquement un compte administrateur.
        </x-ui.alert>

        <div class="flex flex-wrap gap-1.5">
            <a href="{{ route('settings.users.index') }}"
                class="rounded-pill border px-3 py-1.5 text-[12.5px] font-medium {{ ! $roleFilter ? 'border-accent bg-accent-soft text-accent' : 'border-line text-ink-2 hover:bg-hover' }}">
                Tous
            </a>
            @foreach ($tabs as $tab)
                <a href="{{ route('settings.users.index', ['role' => $tab['key']]) }}"
                    class="rounded-pill border px-3 py-1.5 text-[12.5px] font-medium {{ $roleFilter === $tab['key'] ? 'border-accent bg-accent-soft text-accent' : 'border-line text-ink-2 hover:bg-hover' }}">
                    {{ $tab['label'] }} <span class="text-ink-3">{{ $tab['count'] }}</span>
                </a>
            @endforeach
        </div>

        <x-ui.card variant="solid" padding="p-0">
            @if ($users->isEmpty())
                <x-ui.empty-state icon="users" title="Aucun compte" description="Créez les comptes de votre équipe. Les élèves reçoivent le leur depuis leur fiche." class="py-10" />
            @else
                <div class="px-1 pb-1">
                    <x-data.table caption="Liste des utilisateurs">
                        <thead>
                            <tr>
                                <th scope="col">Nom</th>
                                <th scope="col">E-mail</th>
                                <th scope="col">Rôle</th>
                                <th scope="col">Statut</th>
                                <th scope="col" class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td data-label="Nom">
                                        <div class="flex items-center gap-2.5">
                                            <x-ui.avatar :name="$user->name" size="sm" />
                                            <span class="truncate text-sm font-medium text-ink">{{ $user->name }}</span>
                                        </div>
                                    </td>
                                    <td data-label="E-mail"><span class="text-[13px] text-ink-2">{{ $user->email }}</span></td>
                                    <td data-label="Rôle">
                                        <x-ui.badge tone="neutral">{{ $roles[$user->getRoleNames()->first()] ?? 'Utilisateur' }}</x-ui.badge>
                                    </td>
                                    <td data-label="Statut">
                                        <x-ui.badge :tone="$user->is_active ? 'success' : 'danger'">{{ $user->is_active ? 'Actif' : 'Désactivé' }}</x-ui.badge>
                                    </td>
                                    <td data-label="Actions">
                                        <div class="flex flex-wrap justify-end gap-1">
                                            @can('update', $user)
                                                <form method="POST" action="{{ route('settings.users.reset-password', $user) }}">
                                                    @csrf
                                                    <x-ui.button type="submit" size="sm" variant="ghost" icon="lock">Mot de passe</x-ui.button>
                                                </form>
                                                @if ($user->is_active)
                                                    @unless ($user->is(auth()->user()))
                                                        <form method="POST" action="{{ route('settings.users.deactivate', $user) }}">
                                                            @csrf
                                                            <x-ui.button type="submit" size="sm" variant="ghost" class="text-danger" icon="ban">Désactiver</x-ui.button>
                                                        </form>
                                                    @endunless
                                                @else
                                                    <form method="POST" action="{{ route('settings.users.reactivate', $user) }}">
                                                        @csrf
                                                        <x-ui.button type="submit" size="sm" variant="ghost" icon="circle-check">Réactiver</x-ui.button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-data.table>
                </div>
                <x-ui.pagination :paginator="$users" label="Pagination des utilisateurs" />
            @endif
        </x-ui.card>
    </div>

    @can('create', \App\Models\User::class)
        <x-ui.dialog name="new-user" title="Nouvel administrateur" description="Le compte reçoit le rôle administrateur de votre établissement. Un e-mail de définition de mot de passe est envoyé immédiatement.">
            <x-form.errors />
            <form method="POST" action="{{ route('settings.users.store') }}" class="space-y-4">
                @csrf
                <x-form.field name="name" label="Nom complet" required>
                    <x-form.input name="name" required />
                </x-form.field>
                <x-form.field name="email" label="Adresse e-mail" required>
                    <x-form.input name="email" type="email" required />
                </x-form.field>
                <div class="flex justify-end gap-2">
                    <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'new-user' })">Annuler</x-ui.button>
                    <x-ui.button type="submit">Créer l’administrateur</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>
    @endcan
</x-app-layout>
