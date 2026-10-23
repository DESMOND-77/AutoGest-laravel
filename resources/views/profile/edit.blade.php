@php
    $themes = \App\Support\Ui\Theme::cases();
    $accents = [
        'indigo' => 'Indigo', 'violet' => 'Violet', 'rose' => 'Rose', 'orange' => 'Orange',
        'amber' => 'Ambre', 'emerald' => 'Émeraude', 'teal' => 'Sarcelle', 'sky' => 'Ciel',
    ];
@endphp

<x-app-layout title="Mon profil">
    <x-layout.page-header
        title="Mon profil"
        description="Vos informations de connexion, votre mot de passe et l’apparence de l’application."
        :breadcrumbs="[['label' => 'Mon profil']]"
    />

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            @include('profile.partials.update-profile-information-form')
            @include('profile.partials.update-password-form')

            <x-ui.card title="Apparence" description="Ces préférences suivent votre compte sur tous vos appareils." variant="panel">
                <div class="space-y-6">
                    <fieldset>
                        <legend class="text-[13px] font-semibold text-ink">Thème</legend>
                        <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                            <button type="button" class="flex items-center gap-2 rounded-md border border-line p-2.5 text-left text-[13px] hover:bg-hover focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]"
                                :class="$store.prefs.theme === 'auto' && 'border-accent text-accent'"
                                x-on:click="$store.prefs.set('theme', 'auto')">
                                <x-ui.icon name="monitor" class="w-4 h-4" /> Automatique
                            </button>
                            @foreach ($themes as $theme)
                                <button type="button" class="flex items-center gap-2 rounded-md border border-line p-2.5 text-left text-[13px] hover:bg-hover focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]"
                                    :class="$store.prefs.theme === @js($theme->value) && 'border-accent text-accent'"
                                    x-on:click="$store.prefs.set('theme', @js($theme->value))">
                                    <span class="size-4 shrink-0 rounded-full theme-swatch theme-swatch-{{ $theme->value }}" aria-hidden="true"></span>
                                    <span class="min-w-0 truncate">{{ $theme->label() }}</span>
                                </button>
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-[13px] font-semibold text-ink">Couleur d’accent</legend>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <button type="button" class="grid h-9 place-items-center rounded-full border border-line px-3 text-[12px] font-medium text-ink-2 hover:bg-hover"
                                x-on:click="$store.prefs.set('accent', null)">Accent du thème</button>
                            @foreach ($accents as $key => $label)
                                <button type="button" class="grid size-9 place-items-center rounded-full border border-line hover:scale-105 focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]"
                                    x-on:click="$store.prefs.set('accent', @js($key))"
                                    :class="$store.prefs.accent === @js($key) && 'ring-2 ring-accent ring-offset-2 ring-offset-[var(--surface-solid)]'"
                                    aria-label="Accent {{ $label }}" title="{{ $label }}">
                                    <span class="size-5 rounded-full accent-swatch-{{ $key }}" aria-hidden="true"></span>
                                </button>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="grid gap-6 sm:grid-cols-2">
                        <fieldset>
                            <legend class="text-[13px] font-semibold text-ink">Densité</legend>
                            <div class="mt-2 inline-flex rounded-pill bg-hover p-0.5">
                                <button type="button" class="rounded-pill px-3 py-1.5 text-[12.5px] font-medium" :class="$store.prefs.density === 'comfortable' ? 'bg-solid text-ink shadow-sm' : 'text-ink-2'" x-on:click="$store.prefs.set('density', 'comfortable')">Confort</button>
                                <button type="button" class="rounded-pill px-3 py-1.5 text-[12.5px] font-medium" :class="$store.prefs.density === 'compact' ? 'bg-solid text-ink shadow-sm' : 'text-ink-2'" x-on:click="$store.prefs.set('density', 'compact')">Compact</button>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="text-[13px] font-semibold text-ink">Performance</legend>
                            <div class="mt-2 inline-flex rounded-pill bg-hover p-0.5">
                                @foreach (['auto' => 'Auto', 'full' => 'Complet', 'lite' => 'Léger'] as $perfKey => $perfLabel)
                                    <button type="button" class="rounded-pill px-3 py-1.5 text-[12.5px] font-medium" :class="$store.prefs.perf === @js($perfKey) ? 'bg-solid text-ink shadow-sm' : 'text-ink-2'" x-on:click="$store.prefs.set('perf', @js($perfKey))">{{ $perfLabel }}</button>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>

                    <p class="text-[12px] text-ink-3">
                        Le mode « Léger » coupe les flous et les animations : utile sur un appareil modeste ou une connexion lente.
                        Le mode « Automatique » adapte le rendu à votre appareil.
                    </p>
                </div>
            </x-ui.card>
        </div>

        <aside class="space-y-4">
            <x-ui.card title="Votre compte" variant="solid">
                <div class="flex items-center gap-3">
                    <x-ui.avatar :name="auth()->user()->name" size="lg" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-ink">{{ auth()->user()->name }}</p>
                        <p class="truncate text-[12.5px] text-ink-2">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <x-ui.separator class="my-4" />
                <x-data.description-list :columns="1" :items="[
                    ['label' => 'Rôle', 'value' => match (true) {
                        (bool) auth()->user()->hasRole('superadmin') => 'Super-administrateur',
                        (bool) auth()->user()->hasRole('admin') => 'Administrateur',
                        (bool) auth()->user()->hasRole('moniteur') => 'Moniteur',
                        (bool) auth()->user()->hasRole('eleve') => 'Élève',
                        default => 'Utilisateur',
                    }],
                    ['label' => 'Établissement', 'value' => auth()->user()->structure?->name ?? 'Plateforme'],
                    ['label' => 'Compte créé le', 'value' => auth()->user()->created_at?->format('d/m/Y') ?? '—'],
                ]" />
            </x-ui.card>

            @include('profile.partials.delete-user-form')
        </aside>
    </div>
</x-app-layout>
