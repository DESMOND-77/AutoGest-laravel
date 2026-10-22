<x-guest-layout :title="'Créer mon auto-école'">
    <div>
        <p class="text-eyebrow">Inscription</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-ink">Créer mon auto-école</h1>
        <p class="mt-1.5 text-[13.5px] text-ink-2">
            Votre espace est isolé des autres établissements. Il sera activé après validation par l’administrateur de la plateforme.
        </p>
    </div>

    <x-form.errors class="mt-4" />

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-6">
        @csrf

        <x-form.section title="L’auto-école" description="Ces informations apparaissent sur vos factures et documents.">
            <div class="space-y-4">
                <x-form.field name="school_name" label="Nom de l’auto-école" required>
                    <x-form.input name="school_name" icon="building-office" :value="old('school_name')" placeholder="Auto-école de l’Estuaire" autocomplete="organization" required autofocus />
                </x-form.field>
                <x-form.field name="school_phone" label="Téléphone de l’établissement" optional>
                    <x-form.input name="school_phone" type="tel" inputmode="tel" icon="phone" :value="old('school_phone')" placeholder="+241 …" autocomplete="tel" />
                </x-form.field>
            </div>
        </x-form.section>

        <x-form.section title="Votre compte administrateur" description="Ce compte gérera les élèves, les moniteurs et les finances.">
            <div class="space-y-4">
                <x-form.field name="admin_name" label="Nom complet" required>
                    <x-form.input name="admin_name" icon="user" :value="old('admin_name')" autocomplete="name" required />
                </x-form.field>
                <x-form.field name="admin_email" label="Adresse e-mail" required hint="Servira d’identifiant de connexion.">
                    <x-form.input name="admin_email" type="email" icon="envelope" :value="old('admin_email')" autocomplete="email" required />
                </x-form.field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="password" label="Mot de passe" required>
                        <x-form.input name="password" type="password" icon="lock" autocomplete="new-password" required />
                    </x-form.field>
                    <x-form.field name="password_confirmation" label="Confirmer le mot de passe" required>
                        <x-form.input name="password_confirmation" type="password" icon="lock" autocomplete="new-password" required />
                    </x-form.field>
                </div>
            </div>
        </x-form.section>

        <div class="space-y-3">
            <x-ui.button type="submit" size="lg" class="w-full" icon-right="arrow-right">Créer mon établissement</x-ui.button>
            <p class="text-center text-[12px] leading-relaxed text-ink-3">
                En créant un compte, vous acceptez que vos données soient traitées pour la gestion de votre auto-école.
                Aucun paiement n’est demandé à cette étape.
            </p>
        </div>
    </form>

    <p class="mt-6 text-center text-[13px] text-ink-2">
        Déjà inscrit ?
        <a href="{{ route('login') }}" class="font-semibold text-accent hover:underline">Se connecter</a>
    </p>
</x-guest-layout>
