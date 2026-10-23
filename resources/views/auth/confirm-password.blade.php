<x-guest-layout :title="'Confirmer le mot de passe'">
    <div>
        <p class="text-eyebrow">Zone protégée</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink">Confirmez votre mot de passe</h1>
        <p class="mt-1.5 text-[13.5px] text-ink-2">
            Cette action est sensible. Saisissez à nouveau votre mot de passe pour continuer.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4">
        @csrf
        <x-form.field name="password" label="Mot de passe" required>
            <x-form.input name="password" type="password" icon="lock" autocomplete="current-password" autofocus required />
        </x-form.field>
        <x-ui.button type="submit" class="w-full" size="lg">Confirmer</x-ui.button>
    </form>
</x-guest-layout>
