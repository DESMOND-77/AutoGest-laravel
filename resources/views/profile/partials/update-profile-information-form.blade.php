@php
    $user = $user ?? auth()->user();
@endphp

<form id="send-verification" method="POST" action="{{ route('verification.send') }}" class="hidden">@csrf</form>

<x-ui.card title="Informations personnelles" description="Nom et adresse e-mail utilisés pour vous connecter." variant="panel">
    <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
        @csrf
        @method('patch')

        <x-form.errors />

        <x-form.field name="name" label="Nom complet" required>
            <x-form.input name="name" :value="$user->name" required autofocus autocomplete="name" />
        </x-form.field>

        <x-form.field name="email" label="Adresse e-mail" required>
            <x-form.input name="email" type="email" :value="$user->email" required autocomplete="username" />
        </x-form.field>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="rounded-md border border-warning/40 bg-warning-soft px-4 py-3 text-[13px] text-warning" role="status">
                <p class="flex items-center gap-2 font-semibold">
                    <x-ui.icon name="circle-alert" class="w-4 h-4" />
                    Adresse e-mail non vérifiée
                </p>
                <p class="mt-1">Tant qu’elle n’est pas confirmée, certaines notifications ne vous parviendront pas.</p>
                <button form="send-verification" class="mt-2 font-semibold underline underline-offset-2 hover:opacity-80 focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]">
                    Recevoir un nouvel e-mail de vérification
                </button>
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button type="submit">Enregistrer</x-ui.button>
            @if (session('status') === 'profile-updated')
                <p class="text-[13px] font-medium text-success" role="status">Modifications enregistrées.</p>
            @endif
            @if (session('status') === 'verification-link-sent')
                <p class="text-[13px] font-medium text-success" role="status">Un nouvel e-mail de vérification a été envoyé.</p>
            @endif
        </div>
    </form>
</x-ui.card>
