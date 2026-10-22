<x-app-layout :title="'Vérification de l’adresse e-mail'">
    <div class="mx-auto w-full max-w-md py-6">
        <div class="material-panel p-6">
            <span class="grid size-12 place-items-center rounded-full bg-accent-soft text-accent">
                <x-ui.icon name="mail" class="w-6 h-6" />
            </span>
            <h1 class="mt-4 text-xl font-bold tracking-tight text-ink">Vérifiez votre adresse e-mail</h1>
            <p class="mt-1.5 text-[13.5px] text-ink-2">
                Nous avons envoyé un code à 6 chiffres à <strong class="font-semibold text-ink">{{ auth()->user()?->email }}</strong>.
                Saisissez-le ci-dessous pour accéder à votre espace élève.
            </p>

            @if ($errors->any())
                <x-ui.alert tone="danger" class="mt-4">{{ $errors->first() }}</x-ui.alert>
            @endif

            <form method="POST" action="{{ route('eleve.otp.verify') }}" class="mt-5 space-y-4">
                @csrf
                <x-form.field name="code" label="Code de vérification" required>
                    <x-form.otp name="code" :length="6" />
                </x-form.field>
                <x-ui.button type="submit" class="w-full" size="lg">Vérifier mon adresse</x-ui.button>
            </form>

            <form method="POST" action="{{ route('eleve.otp.resend') }}" class="mt-3">
                @csrf
                <x-ui.button type="submit" variant="ghost" class="w-full" icon="refresh-cw">Renvoyer un code</x-ui.button>
            </form>

            <p class="mt-4 text-[12px] leading-relaxed text-ink-3">
                Le renvoi est limité à une demande par minute. Si vous ne recevez rien, vérifiez le dossier « indésirables » de votre boîte e-mail.
            </p>
        </div>
    </div>
</x-app-layout>
