@php
    $isPdf = str_ends_with(strtolower((string) $document->original_name), '.pdf') || str_ends_with(strtolower((string) $document->path), '.pdf');
    $isImage = (bool) preg_match('/\.(jpe?g|png|webp|gif)$/i', (string) $document->original_name);
    $stream = route('documents.stream', $document);
@endphp

<x-app-layout :title="$document->original_name">
    <x-layout.page-header
        :title="$document->requiredDocumentType?->label ?? $document->type?->label() ?? 'Document'"
        :description="$document->original_name.' · version '.$document->version.' · déposé le '.($document->created_at?->format('d/m/Y') ?? '—')"
        :breadcrumbs="[['label' => 'Documents'], ['label' => $document->original_name]]"
    >
        <x-slot:actions>
            <x-ui.status :value="$document->review_status" />
            <x-ui.button :href="route('documents.download', $document)" variant="secondary" icon="download">Télécharger</x-ui.button>
            @can('review', $document)
                <form method="POST" action="{{ route('documents.approve', $document) }}">
                    @csrf
                    <x-ui.button type="submit" icon="check">Approuver</x-ui.button>
                </form>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-ui.card variant="solid" padding="p-0">
                @if ($isImage)
                    <img src="{{ $stream }}" alt="Aperçu de {{ $document->original_name }}" class="max-h-[70vh] w-full rounded-t-lg object-contain bg-[var(--surface-inset)]">
                @elseif ($isPdf)
                    <iframe src="{{ $stream }}" title="Aperçu PDF de {{ $document->original_name }}" class="h-[70vh] w-full rounded-t-lg bg-[var(--surface-inset)]"></iframe>
                @else
                    <x-ui.empty-state icon="file-text" title="Aperçu indisponible" description="Ce format ne peut pas être affiché dans le navigateur. Téléchargez le fichier pour le consulter." class="py-12">
                        <x-slot:actions>
                            <x-ui.button :href="route('documents.download', $document)" icon="download">Télécharger</x-ui.button>
                        </x-slot:actions>
                    </x-ui.empty-state>
                @endif
                <p class="border-t border-line px-4 py-2.5 text-[12px] text-ink-3">
                    Le fichier est servi par l’application : seuls les utilisateurs autorisés y ont accès.
                </p>
            </x-ui.card>
        </div>

        <aside class="space-y-4">
            <x-ui.card title="Informations" variant="panel">
                <x-data.description-list :columns="1" :items="[
                    ['label' => 'Type', 'value' => $document->type?->label() ?? '—'],
                    ['label' => 'Version', 'value' => (string) $document->version],
                    ['label' => 'Déposé par', 'value' => $document->uploadedBy?->name ?? 'Système'],
                    ['label' => 'Déposé le', 'value' => $document->created_at?->format('d/m/Y H:i') ?? '—'],
                    ['label' => 'Expire le', 'value' => $document->expires_at?->format('d/m/Y') ?? '—'],
                ]" />
            </x-ui.card>

            @if ($document->rejection_reason)
                <x-ui.alert tone="danger" title="Pièce rejetée">{{ $document->rejection_reason }}</x-ui.alert>
            @endif

            @can('review', $document)
                <x-ui.card title="Validation" variant="solid">
                    <p class="text-[13px] text-ink-2">Rejetez la pièce avec un motif clair : l’élève devra la remplacer.</p>
                    <x-ui.button variant="ghost" class="mt-2 text-danger" icon="x"
                        x-on:click="$dispatch('open-dialog', { name: 'reject-document' })">Rejeter la pièce</x-ui.button>

                    <x-ui.dialog name="reject-document" title="Rejeter cette pièce" description="Le motif est communiqué à l’élève.">
                        <form method="POST" action="{{ route('documents.reject', $document) }}" class="space-y-4">
                            @csrf
                            <x-form.field name="reason" label="Motif du rejet" required>
                                <x-form.textarea name="reason" rows="3" required></x-form.textarea>
                            </x-form.field>
                            <div class="flex justify-end gap-2">
                                <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'reject-document' })">Annuler</x-ui.button>
                                <x-ui.button variant="danger" type="submit">Rejeter</x-ui.button>
                            </div>
                        </form>
                    </x-ui.dialog>
                </x-ui.card>
            @endcan
        </aside>
    </div>
</x-app-layout>
