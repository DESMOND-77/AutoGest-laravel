@php
    $messages = [
        'invalid' => ['title' => 'Lien incomplet', 'body' => 'Ce lien d’inscription ne contient pas de jeton. Demandez le lien complet à votre auto-école.'],
        'expired' => ['title' => 'Lien expiré', 'body' => 'Ce lien d’inscription a expiré. Votre auto-école peut en générer un nouveau en un clic.'],
        'revoked' => ['title' => 'Lien révoqué', 'body' => 'Ce lien a été désactivé par votre auto-école. Demandez un nouveau lien au secrétariat.'],
        'exhausted' => ['title' => 'Lien déjà utilisé', 'body' => 'Ce lien a atteint son nombre maximal d’inscriptions. Demandez-en un nouveau.'],
    ];
    $state = $state ?? 'invalid';
@endphp

<x-public-layout :title="$state === 'form' ? 'Inscription élève' : ($messages[$state]['title'] ?? 'Inscription élève')">
    <div class="mx-auto w-full max-w-2xl px-4 py-10 sm:px-6">
        @if ($state !== 'form')
            <div class="material-panel p-6 text-center sm:p-10">
                <span class="mx-auto grid size-14 place-items-center rounded-full bg-warning-soft text-warning">
                    <x-ui.icon name="circle-alert" class="w-7 h-7" />
                </span>
                <h1 class="mt-4 text-2xl font-bold tracking-tight text-ink">{{ $messages[$state]['title'] ?? 'Lien inutilisable' }}</h1>
                <p class="mx-auto mt-2 max-w-md text-[14px] text-ink-2">{{ $messages[$state]['body'] ?? 'Ce lien d’inscription n’est pas valide.' }}</p>
                <p class="mt-6 text-[12.5px] text-ink-3">Besoin d’aide ? Contactez le secrétariat de votre auto-école.</p>
            </div>
        @else
            <div class="mb-6">
                <p class="text-eyebrow">Inscription en ligne</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink">Inscrivez-vous chez {{ $structure?->name ?? 'votre auto-école' }}</h1>
                <p class="mt-1.5 text-[14px] text-ink-2">
                    Renseignez vos informations. Votre auto-école validera votre dossier et vous accompagnera pour la suite du parcours.
                </p>
            </div>

            @if (! empty($duplicateError))
                <x-ui.alert tone="warning" title="Inscription déjà existante" class="mb-4">{{ $duplicateError }}</x-ui.alert>
            @endif

            <x-form.errors class="mb-4" />

            <form method="POST" action="{{ route('public-registration.store') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="registration_token" value="{{ $token }}">

                <div class="material-solid p-5">
                    <x-form.section title="Vos informations" description="Identité et coordonnées.">
                        <div class="space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-form.field name="first_name" label="Prénom" required>
                                    <x-form.input name="first_name" :value="old('first_name')" autocomplete="given-name" required />
                                </x-form.field>
                                <x-form.field name="last_name" label="Nom" required>
                                    <x-form.input name="last_name" :value="old('last_name')" autocomplete="family-name" required />
                                </x-form.field>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-form.field name="birth_date" label="Date de naissance" required>
                                    <x-form.input name="birth_date" type="date" :value="old('birth_date')" required />
                                </x-form.field>
                                <x-form.field name="birth_place" label="Lieu de naissance" optional>
                                    <x-form.input name="birth_place" :value="old('birth_place')" />
                                </x-form.field>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-form.field name="phone" label="Téléphone" required>
                                    <x-form.input name="phone" type="tel" inputmode="tel" icon="phone" :value="old('phone')" placeholder="+241 …" required />
                                </x-form.field>
                                <x-form.field name="email" label="Adresse e-mail" required hint="Servira d’identifiant de connexion.">
                                    <x-form.input name="email" type="email" icon="envelope" :value="old('email')" autocomplete="email" required />
                                </x-form.field>
                            </div>
                            <x-form.field name="address" label="Adresse" optional>
                                <x-form.input name="address" :value="old('address')" autocomplete="street-address" />
                            </x-form.field>
                        </div>
                    </x-form.section>
                </div>

                <div class="material-solid p-5">
                    <x-form.section title="Votre formation" description="Le permis visé et la formule choisie.">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-form.field name="license_category" label="Catégorie de permis" required>
                                <x-form.select name="license_category" :selected="old('license_category', 'B')"
                                    :options="collect($licenseCategories)->mapWithKeys(fn ($c) => [$c->value => 'Permis '.$c->value])->all()" />
                            </x-form.field>
                            <x-form.field name="course_type" label="Formule" required>
                                <x-form.select name="course_type" :selected="old('course_type')"
                                    :options="collect($courseTypes)->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" placeholder="Choisir une formule" />
                            </x-form.field>
                        </div>
                    </x-form.section>
                </div>

                <div class="material-solid p-5">
                    <x-form.section title="Votre accès" description=" Vous pourrez suivre votre dossier, votre planning et vos paiements.">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-form.field name="password" label="Mot de passe" required>
                                <x-form.input name="password" type="password" icon="lock" autocomplete="new-password" required />
                            </x-form.field>
                            <x-form.field name="password_confirmation" label="Confirmer le mot de passe" required>
                                <x-form.input name="password_confirmation" type="password" icon="lock" autocomplete="new-password" required />
                            </x-form.field>
                        </div>
                    </x-form.section>
                </div>

                <x-ui.button type="submit" size="lg" class="w-full" icon-right="arrow-right">Valider mon inscription</x-ui.button>

                <p class="text-center text-[12px] leading-relaxed text-ink-3">
                    Un code de vérification sera envoyé à votre adresse e-mail pour sécuriser votre première connexion.
                </p>
            </form>
        @endif
    </div>
</x-public-layout>
