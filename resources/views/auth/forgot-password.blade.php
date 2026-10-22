<x-guest-layout :title="'Mot de passe oublié'">
    <div>
        <p class="text-eyebrow">Accès au compte</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink">Mot de passe oublié</h1>
        <p class="mt-1.5 text-[13.5px] text-ink-2">
            Indiquez l’adresse e-mail de votre compte : nous vous envoyons un lien pour choisir un nouveau mot de passe.
        </p>
    </div>

    @if (session('status'))
        <x-ui.alert tone="success" class="mt-4">{{ session('status') }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf
        <x-form.field name="email" label="Adresse e-mail" required>
            <x-form.input name="email" type="email" icon="envelope" :value="old('email')" autocomplete="username" autofocus required />
        </x-form.field>
        <x-ui.button type="submit" class="w-full" size="lg">Envoyer le lien de réinitialisation</x-ui.button>
    </form>

    <p class="mt-6 text-center text-[13px] text-ink-2">
        <a href="{{ route('login') }}" class="font-medium text-accent hover:underline">Retour à la connexion</a>
    </p>
</x-guest-layout>
