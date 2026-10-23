<x-ui.card title="Zone sensible" variant="panel">
    <p class="text-[13px] leading-relaxed text-ink-2">
        Supprimer votre compte vous déconnecte immédiatement et retire vos accès. Cette action est définitive.
    </p>

    <x-ui.button variant="danger" size="sm" class="mt-3" icon="trash-2" x-on:click="$dispatch('open-dialog', { name: 'delete-account' })">
        Supprimer mon compte
    </x-ui.button>

    <x-ui.dialog name="delete-account" title="Supprimer définitivement ce compte ?" description="Saisissez votre mot de passe pour confirmer. Cette action ne peut pas être annulée.">
        <form method="POST" action="{{ route('profile.destroy') }}" class="space-y-4">
            @csrf
            @method('delete')

            @if ($errors->userDeletion->any())
                <x-ui.alert tone="danger">{{ $errors->userDeletion->first() }}</x-ui.alert>
            @endif

            <x-form.field name="password" label="Votre mot de passe" required>
                <x-form.input name="password" type="password" required autocomplete="current-password" />
            </x-form.field>

            <div class="flex justify-end gap-2">
                <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'delete-account' })">Annuler</x-ui.button>
                <x-ui.button variant="danger" type="submit">Supprimer mon compte</x-ui.button>
            </div>
        </form>
    </x-ui.dialog>
</x-ui.card>
