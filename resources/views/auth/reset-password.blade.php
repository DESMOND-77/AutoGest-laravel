<x-guest-layout :title="'Nouveau mot de passe'">
    <div>
        <p class="text-eyebrow">Sécurité</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink">Choisir un nouveau mot de passe</h1>
        <p class="mt-1.5 text-[13.5px] text-ink-2">Utilisez au moins 8 caractères, avec lettres et chiffres.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-form.field name="email" label="Adresse e-mail" required>
            <x-form.input name="email" type="email" icon="envelope" :value="old('email', $request->email)" autocomplete="username" required />
        </x-form.field>
        <x-form.field name="password" label="Nouveau mot de passe" required>
            <x-form.input name="password" type="password" icon="lock" autocomplete="new-password" required autofocus />
        </x-form.field>
        <x-form.field name="password_confirmation" label="Confirmer le mot de passe" required>
            <x-form.input name="password_confirmation" type="password" icon="lock" autocomplete="new-password" required />
        </x-form.field>

        <x-ui.button type="submit" class="w-full" size="lg">Enregistrer le mot de passe</x-ui.button>
    </form>
</x-guest-layout>
