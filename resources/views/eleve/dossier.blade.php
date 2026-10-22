@php
    $approved = $types->filter(fn ($type) => ($documentsByType->get($type->id)?->review_status?->value ?? null) === 'approved')->count();
    $percent = $types->isEmpty() ? 0 : (int) round($approved / $types->count() * 100);
@endphp

<x-app-layout :title="'Mon dossier'">
    <x-layout.page-header
        title="Mon dossier"
        description="Déposez les pièces demandées par votre auto-école. Chaque pièce est vérifiée par le secrétariat."
        :breadcrumbs="[['label' => 'Accueil', 'href' => route('eleve.dashboard')], ['label' => 'Mon dossier']]"
    >
        <x-slot:actions>
            <x-ui.status :value="$student->dossier_status" />
        </x-slot:actions>
    </x-layout.page-header>

    <div class="mx-auto max-w-2xl space-y-4">
        @if ($errors->any())
            <x-ui.alert tone="danger" title="Dépôt impossible">{{ $errors->first() }}</x-ui.alert>
        @endif

        <x-ui.card variant="panel">
            <div class="space-y-3">
                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="text-[12px] uppercase tracking-wide text-ink-3">Pièces validées</p>
                        <p class="text-2xl font-bold text-ink">{{ $approved }} / {{ $types->count() }}</p>
                    </div>
                    <p class="text-[13px] text-ink-2">{{ $percent }} %</p>
                </div>
                <x-ui.progress :value="$percent" label="Avancement du dossier" />
                <p class="text-[12.5px] text-ink-3">
                    Les formats acceptés sont le PDF, le JPG et le PNG (10 Mo maximum par fichier). Une pièce refusée peut être remplacée.
                </p>
            </div>
        </x-ui.card>

        @if ($types->isEmpty())
            <x-ui.card variant="solid">
                <x-ui.empty-state icon="folder" title="Aucune pièce demandée" description="Votre auto-école n’a pas encore configuré les pièces du dossier." />
            </x-ui.card>
        @else
            <div class="space-y-3">
                @foreach ($types as $type)
                    @php
                        $document = $documentsByType->get($type->id);
                        $status = $document?->review_status;
                    @endphp
                    <article class="material-solid p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h2 class="text-[14px] font-semibold text-ink">{{ $type->label }}</h2>
                                <p class="mt-0.5 text-[12px] text-ink-3">
                                    @if ($document)
                                        {{ $document->original_name }} · déposé le {{ $document->created_at?->format('d/m/Y') }}
                                    @else
                                        Aucune pièce déposée
                                    @endif
                                </p>
                                @if ($document?->rejection_reason)
                                    <p class="mt-1.5 text-[12.5px] font-medium text-danger">Motif du refus : {{ $document->rejection_reason }}</p>
                                @endif
                            </div>
                            @if ($status)
                                <x-ui.status :value="$status" />
                            @else
                                <x-ui.badge tone="neutral">À déposer</x-ui.badge>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('eleve.dossier.upload', $type) }}" enctype="multipart/form-data" class="mt-3 space-y-2">
                            @csrf
                            <x-form.file-drop name="file" :accept="'.pdf,.jpg,.jpeg,.png'" :hint="'Déposer '.$type->label" />
                            <div class="flex flex-wrap items-center gap-2">
                                <x-ui.button type="submit" size="sm" variant="secondary" icon="upload">{{ $document ? 'Remplacer la pièce' : 'Déposer la pièce' }}</x-ui.button>
                                @if ($document)
                                    <x-ui.button :href="route('documents.show', $document)" size="sm" variant="ghost" icon="eye">Consulter</x-ui.button>
                                @endif
                            </div>
                        </form>
                    </article>
                @endforeach
            </div>

            <x-ui.card variant="solid">
                @if ($canSubmit)
                    <form method="POST" action="{{ route('eleve.dossier.submit') }}">
                        @csrf
                        <p class="text-[13.5px] text-ink-2">Toutes vos pièces sont validées. Vous pouvez soumettre votre dossier : votre parcours passera à l’étape « Inscription ».</p>
                        <x-ui.button type="submit" class="mt-3" icon="check">Soumettre mon dossier</x-ui.button>
                    </form>
                @else
                    <p class="text-[13.5px] text-ink-2">
                        La soumission sera possible quand toutes les pièces seront approuvées par votre auto-école. Vous serez alors averti dans votre espace.
                    </p>
                    <x-ui.button type="button" class="mt-3" variant="secondary" disabled>En attente des validations</x-ui.button>
                @endif
            </x-ui.card>
        @endif
    </div>
</x-app-layout>
