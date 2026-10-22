<x-guest-layout>
    @if ($state === 'form')
        <div class="mb-5 text-center">
            <p class="text-xs font-semibold text-primary tracking-wide uppercase mb-1">Inscription élève</p>
            <h1 class="text-lg font-semibold text-content">{{ $structure->name }}</h1>
            @if ($structure->address || $structure->phone)
                <p class="text-sm text-content-secondary mt-1">
                    {{ collect([$structure->address, $structure->phone])->filter()->implode(' · ') }}
                </p>
            @endif
        </div>

        @if ($duplicateError ?? null)
            <x-alert variant="danger" class="mb-4">{{ $duplicateError }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert variant="danger" class="mb-4">
                Veuillez corriger les champs indiqués ci-dessous.
            </x-alert>
        @endif

        <form method="POST" action="{{ route('public-registration.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="registration_token" value="{{ $token }}">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="first_name" value="Prénom" />
                    <x-text-input id="first_name" name="first_name" class="block mt-1 w-full" :value="old('first_name')" required autofocus />
                    <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="last_name" value="Nom" />
                    <x-text-input id="last_name" name="last_name" class="block mt-1 w-full" :value="old('last_name')" required />
                    <x-input-error :messages="$errors->get('last_name')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="phone" value="Téléphone" />
                <x-text-input id="phone" type="tel" name="phone" class="block mt-1 w-full" :value="old('phone')" required />
                <x-input-error :messages="$errors->get('phone')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="email" value="E-mail" />
                <x-text-input id="email" type="email" name="email" class="block mt-1 w-full" :value="old('email')" required />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="password" value="Mot de passe" />
                    <x-text-input id="password" type="password" name="password" class="block mt-1 w-full" required />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="password_confirmation" value="Confirmer le mot de passe" />
                    <x-text-input id="password_confirmation" type="password" name="password_confirmation" class="block mt-1 w-full" required />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="license_category" value="Catégorie de permis" />
                    <select id="license_category" name="license_category" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        @foreach ($licenseCategories as $case)
                            <option value="{{ $case->value }}" @selected(old('license_category') === $case->value)>{{ $case->value }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('license_category')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="course_type" value="Formation" />
                    <select id="course_type" name="course_type" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        @foreach ($courseTypes as $case)
                            <option value="{{ $case->value }}" @selected(old('course_type') === $case->value)>{{ $case->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('course_type')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="birth_date" value="Date de naissance" />
                <x-text-input id="birth_date" type="date" name="birth_date" class="block mt-1 w-full" :value="old('birth_date')" required />
                <x-input-error :messages="$errors->get('birth_date')" class="mt-1" />
            </div>

            <div>
                <x-input-label for="address" value="Adresse (optionnel)" />
                <x-text-input id="address" name="address" class="block mt-1 w-full" :value="old('address')" />
                <x-input-error :messages="$errors->get('address')" class="mt-1" />
            </div>

            <x-primary-button class="w-full justify-center">S'inscrire</x-primary-button>
        </form>

        <p class="text-xs text-content-muted text-center mt-5">
            🔒 Vos informations sont transmises de manière sécurisée à {{ $structure->name }}.
        </p>
    @else
        <div class="text-center py-4">
            <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-danger/10 text-danger mb-4">
                <x-icon name="exclamation-triangle" class="w-6 h-6" />
            </span>

            @if ($state === 'expired')
                <h1 class="text-lg font-semibold text-content mb-2">Lien d'inscription expiré</h1>
                <p class="text-sm text-content-secondary">
                    Ce lien d'inscription a expiré. Demandez à l'auto-école de vous transmettre un nouveau lien.
                </p>
            @else
                <h1 class="text-lg font-semibold text-content mb-2">Lien d'inscription invalide</h1>
                <p class="text-sm text-content-secondary">
                    Ce lien d'inscription est invalide, expiré ou désactivé. Veuillez demander un nouveau lien à votre auto-école.
                </p>
            @endif

            <a href="/" class="inline-block mt-6 text-sm text-primary hover:underline">Retour à l'accueil</a>
        </div>
    @endif
</x-guest-layout>
