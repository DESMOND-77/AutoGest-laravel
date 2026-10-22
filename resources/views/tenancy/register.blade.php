<x-guest-layout>
    <h1 class="text-lg font-semibold text-content mb-2">
        Inscrire mon auto-école
    </h1>

    <p class="text-sm text-content-secondary mb-6">
        Créez le compte de votre établissement. Il sera activé après validation
        par l'administrateur de la plateforme.
    </p>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <x-input-label for="school_name" value="Nom de l'auto-école" />
            <x-text-input id="school_name" class="block mt-1 w-full" type="text" name="school_name" :value="old('school_name')" required autofocus />
            <x-input-error :messages="$errors->get('school_name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="school_phone" value="Téléphone de l'établissement" />
            <x-text-input id="school_phone" class="block mt-1 w-full" type="text" name="school_phone" :value="old('school_phone')" />
            <x-input-error :messages="$errors->get('school_phone')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="admin_name" value="Nom de l'administrateur" />
            <x-text-input id="admin_name" class="block mt-1 w-full" type="text" name="admin_name" :value="old('admin_name')" required />
            <x-input-error :messages="$errors->get('admin_name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="admin_email" value="E-mail de l'administrateur" />
            <x-text-input id="admin_email" class="block mt-1 w-full" type="email" name="admin_email" :value="old('admin_email')" required />
            <x-input-error :messages="$errors->get('admin_email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Mot de passe" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Confirmer le mot de passe" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required />
        </div>

        <div class="flex items-center justify-end mt-6">
            <a class="text-sm text-content-secondary hover:text-content" href="{{ route('login') }}">
                Déjà inscrit ?
            </a>

            <x-primary-button class="ms-3">
                Inscrire mon auto-école
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
