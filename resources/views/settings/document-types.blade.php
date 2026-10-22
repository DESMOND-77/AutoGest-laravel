<x-app-layout :title="'Pièces du dossier'">
    <x-layout.page-header
        title="Pièces du dossier"
        description="Les pièces que chaque élève doit déposer avant de pouvoir soumettre son dossier."
        :breadcrumbs="[['label' => 'Paramètres', 'href' => route('settings.show')], ['label' => 'Pièces du dossier']]"
    >
        <x-slot:actions>
            @can('create', \App\Domain\Students\Models\RequiredDocumentType::class)
                <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-doc-type' })">Ajouter une pièce</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    <div class="mx-auto max-w-2xl space-y-4">
        <x-ui.alert tone="info">
            Ajouter une pièce relance la vérification des dossiers en cours : un élève déjà validé repassera en « dossier incomplet » jusqu’au dépôt de la nouvelle pièce.
        </x-ui.alert>

        @if ($types->isEmpty())
            <x-ui.card variant="solid">
                <x-ui.empty-state icon="folder" title="Aucune pièce exigée" description="Sans pièce obligatoire, les élèves peuvent soumettre un dossier vide.">
                    <x-slot:actions>
                        @can('create', \App\Domain\Students\Models\RequiredDocumentType::class)
                            <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-doc-type' })">Ajouter une pièce</x-ui.button>
                        @endcan
                    </x-slot:actions>
                </x-ui.empty-state>
            </x-ui.card>
        @else
            <x-ui.card variant="solid" padding="p-0">
                <ul class="divide-y divide-line">
                    @foreach ($types as $type)
                        <li class="flex items-center justify-between gap-3 px-5 py-3">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <span class="grid size-8 shrink-0 place-items-center rounded-sm bg-[var(--surface-inset)] text-ink-3">
                                    <x-ui.icon name="file-text" class="w-4 h-4" />
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-[13.5px] font-medium text-ink">{{ $type->label }}</p>
                                    <p class="text-[11.5px] text-ink-3">Position {{ $type->position }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-ui.badge :tone="$type->is_active ? 'success' : 'neutral'">{{ $type->is_active ? 'Active' : 'Inactive' }}</x-ui.badge>
                                @can('update', $type)
                                    <form method="POST" action="{{ route('settings.document-types.update', $type) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $type->is_active ? 0 : 1 }}">
                                        <x-ui.button type="submit" size="sm" variant="ghost">{{ $type->is_active ? 'Désactiver' : 'Activer' }}</x-ui.button>
                                    </form>
                                @endcan
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        @endif
    </div>

    @can('create', \App\Domain\Students\Models\RequiredDocumentType::class)
        <x-ui.dialog name="new-doc-type" title="Ajouter une pièce" description="Le libellé est visible par l’élève dans son dossier.">
            <x-form.errors />
            <form method="POST" action="{{ route('settings.document-types.store') }}" class="space-y-4">
                @csrf
                <x-form.field name="label" label="Libellé de la pièce" required>
                    <x-form.input name="label" placeholder="Copie de la pièce d’identité" required />
                </x-form.field>
                <div class="flex justify-end gap-2">
                    <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'new-doc-type' })">Annuler</x-ui.button>
                    <x-ui.button type="submit">Ajouter la pièce</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>
    @endcan
</x-app-layout>
