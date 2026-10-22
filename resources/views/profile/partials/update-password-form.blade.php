<x-ui.card title="Mot de passe" description="Choisissez une phrase secrète longue et unique." variant="panel">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('put')

        @if ($errors->updatePassword->any())
            <x-ui.alert tone="danger" title="Le mot de passe n’a pas été modifié">
                {{ $errors->updatePassword->first() }}
            </x-ui.alert>
        @endif

        <x-form.field name="current_password" label="Mot de passe actuel" required>
            <x-form.input name="current_password" type="password" required autocomplete="current-password" />
        </x-form.field>

        <x-form.field name="password" label="Nouveau mot de passe" hint="12 caractères minimum, avec une majuscule, une minuscule et un chiffre." required>
            <x-form.input name="password" type="password" required autocomplete="new-password" />
        </x-form.field>

        <x-form.field name="password_confirmation" label="Confirmation du nouveau mot de passe" required>
            <x-form.input name="password_confirmation" type="password" required autocomplete="new-password" />
        </x-form.field>

        <div class="flex flex-wrap items-center gap-3">
            <x-ui.button type="submit">Modifier le mot de passe</x-ui.button>
            @if (session('status') === 'password-updated')
                <p class="text-[13px] font-medium text-success" role="status">Mot de passe modifié.</p>
            @endif
        </div>
    </form>
</x-ui.card>
