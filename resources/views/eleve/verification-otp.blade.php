<x-guest-layout>
    <div class="mb-5 text-center">
        <p class="text-xs font-semibold text-primary tracking-wide uppercase mb-1">Vérification</p>
        <h1 class="text-lg font-semibold text-content">Confirmez votre adresse e-mail</h1>
        <p class="text-sm text-content-secondary mt-1">
            Saisissez le code à 6 chiffres envoyé à {{ auth()->user()->email }}.
        </p>
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('eleve.otp.verify') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="code" value="Code de vérification" />
            <x-text-input id="code" name="code" inputmode="numeric" maxlength="6" class="block mt-1 w-full text-center tracking-[0.5em]" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-1" />
        </div>
        <x-primary-button class="w-full justify-center">Vérifier</x-primary-button>
    </form>

    <form method="POST" action="{{ route('eleve.otp.resend') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm text-primary hover:underline">Renvoyer le code</button>
    </form>
</x-guest-layout>
