<x-guest-layout :title="'Vérifier l’adresse e-mail'">
    <div>
        <p class="text-eyebrow">Vérification</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink">Vérifiez votre adresse e-mail</h1>
        <p class="mt-1.5 text-[13.5px] text-ink-2">
            Un lien de confirmation a été envoyé à <strong class="font-semibold text-ink">{{ auth()->user()?->email }}</strong>.
            Ouvrez-le pour activer toutes les fonctionnalités de votre espace.
        </p>
    </div>

    @if (session('status'))
        <x-ui.alert tone="success" class="mt-4">{{ session('status') }}</x-ui.alert>
    @endif

    <div class="mt-6 space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-ui.button type="submit" class="w-full" icon="mail">Renvoyer le lien de vérification</x-ui.button>
        </form>
        <p class="text-center text-[12.5px] text-ink-3">L’envoi est limité à quelques demandes par minute.</p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-ui.button type="submit" variant="ghost" class="w-full">Se déconnecter</x-ui.button>
        </form>
    </div>
</x-guest-layout>
