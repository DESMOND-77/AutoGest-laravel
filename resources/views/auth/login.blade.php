<x-guest-layout :title="'Connexion'">
    <div>
        <p class="text-eyebrow">Espace professionnel</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink">Connexion</h1>
        <p class="mt-1.5 text-[13.5px] text-ink-2">Accédez au tableau de bord de votre auto-école.</p>
    </div>

    @if (session('status'))
        <x-ui.alert tone="success" class="mt-4">{{ session('status') }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <x-form.field name="email" label="Adresse e-mail" required>
            <x-form.input name="email" type="email" icon="envelope" :value="old('email')" autocomplete="username" autofocus required />
        </x-form.field>

        <x-form.field name="password" label="Mot de passe" required>
            <x-form.input name="password" type="password" icon="lock" autocomplete="current-password" required />
        </x-form.field>

        <div class="flex flex-wrap items-center justify-between gap-2">
            <x-form.checkbox name="remember" label="Se souvenir de moi" />
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-[13px] font-medium text-accent hover:underline">Mot de passe oublié ?</a>
            @endif
        </div>

        <x-ui.button type="submit" class="w-full" size="lg" icon-right="arrow-right">Se connecter</x-ui.button>
    </form>

    <p class="mt-6 text-center text-[13px] text-ink-2">
        Pas encore de compte ?
        <a href="{{ route('register') }}" class="font-semibold text-accent hover:underline">Créer mon auto-école</a>
    </p>

    <p class="mt-8 rounded-md border border-line p-3 text-[12px] leading-relaxed text-ink-3">
        <strong class="font-semibold text-ink-2">Élève ?</strong> Utilisez l’adresse e-mail transmise par votre auto-école. Un code de vérification vous sera demandé à la première connexion.
    </p>

    @if (session('toast'))
        <script>window.addEventListener('DOMContentLoaded', () => window.Alpine?.store('toasts')?.push(@js(session('toast')), 'info'));</script>
    @endif
</x-guest-layout>
