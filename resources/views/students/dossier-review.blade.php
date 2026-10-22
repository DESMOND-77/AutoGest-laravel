@php
    $pending = $students->filter(fn ($student) => $student->documents->contains(fn ($document) => $document->review_status?->value === 'pending'));
    $quickReasons = [
        'Document illisible ou flou',
        'Document expiré',
        'Nom ne correspondant pas à l’élève',
        'Pièce incomplète (recto/verso manquant)',
    ];
@endphp

<x-app-layout :title="'Dossiers à valider'">
    <x-layout.page-header
        title="Dossiers à valider"
        :description="$pending->count().' élève(s) avec des pièces en attente. Traitez les plus anciens d’abord.'"
        :breadcrumbs="[['label' => 'Élèves', 'href' => route('students.index')], ['label' => 'Dossiers à valider']]"
    />

    @if ($students->isEmpty())
        <x-ui.card variant="solid">
            <x-ui.empty-state icon="clipboard-check" title="Aucun dossier à valider" description="Toutes les pièces déposées ont été traitées. Les nouveaux dépôts apparaîtront ici.">
                <x-slot:actions>
                    <x-ui.button :href="route('students.index')" variant="secondary" icon="graduation-cap">Voir les élèves</x-ui.button>
                </x-slot:actions>
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <div class="grid gap-4 lg:grid-cols-5">
            {{-- Liste --}}
            <section class="space-y-3 lg:col-span-2" aria-label="Élèves en attente">
                @foreach ($students as $student)
                    @php
                        $documents = $student->documents->sortBy(fn ($d) => $d->created_at);
                        $pendingCount = $documents->filter(fn ($d) => $d->review_status?->value === 'pending')->count();
                    @endphp
                    <article class="material-solid p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <a href="{{ route('students.show', $student) }}" class="block truncate text-sm font-semibold text-ink hover:text-accent">{{ $student->fullName() }}</a>
                                <p class="text-[12px] text-ink-3">{{ $documents->count() }} pièce(s) · {{ $pendingCount }} en attente</p>
                            </div>
                            <x-ui.badge :tone="$pendingCount > 0 ? 'warning' : 'success'">{{ $pendingCount > 0 ? 'À traiter' : 'Traité' }}</x-ui.badge>
                        </div>

                        <ul class="mt-3 space-y-2">
                            @foreach ($documents as $document)
                                <li class="rounded-sm border border-line p-2.5">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="truncate text-[12.5px] font-medium text-ink">{{ $document->requiredDocumentType?->label ?? $document->type?->label() }}</p>
                                            <p class="truncate text-[11.5px] text-ink-3">{{ $document->original_name }}</p>
                                        </div>
                                        <x-ui.status :value="$document->review_status" />
                                    </div>

                                    @if ($document->rejection_reason)
                                        <p class="mt-1.5 text-[11.5px] font-medium text-danger">Motif : {{ $document->rejection_reason }}</p>
                                    @endif

                                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                        <x-ui.button :href="route('documents.show', $document)" size="sm" variant="ghost" icon="eye">Voir</x-ui.button>

                                        <form method="POST" action="{{ route('documents.approve', $document) }}">
                                            @csrf
                                            <x-ui.button type="submit" size="sm" variant="secondary" icon="check">Approuver</x-ui.button>
                                        </form>

                                        <button type="button" class="inline-flex h-9 items-center gap-1.5 rounded-pill px-3 text-[13px] font-medium text-danger hover:bg-danger-soft focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]"
                                            x-on:click="$dispatch('open-dialog', { name: 'reject-{{ $document->id }}' })">
                                            <x-ui.icon name="x" class="w-4 h-4" /> Rejeter
                                        </button>
                                    </div>

                                    <x-ui.dialog name="reject-{{ $document->id }}" title="Rejeter cette pièce" description="Le motif est obligatoire : l’élève doit savoir quoi corriger.">
                                        <form method="POST" action="{{ route('documents.reject', $document) }}" class="space-y-3">
                                            @csrf
                                            <x-form.field name="reason" label="Motif du rejet" required>
                                                <x-form.textarea name="reason" rows="3" required></x-form.textarea>
                                            </x-form.field>
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach ($quickReasons as $reason)
                                                    <button type="button" class="rounded-pill border border-line px-2.5 py-1 text-[12px] text-ink-2 hover:bg-hover focus-visible:outline-none focus-visible:shadow-[var(--focus-ring)]"
                                                        x-on:click="$el.closest('form').querySelector('textarea').value = @js($reason)">
                                                        {{ $reason }}
                                                    </button>
                                                @endforeach
                                            </div>
                                            <div class="flex justify-end gap-2">
                                                <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'reject-{{ $document->id }}' })">Annuler</x-ui.button>
                                                <x-ui.button variant="danger" type="submit">Rejeter la pièce</x-ui.button>
                                            </div>
                                        </form>
                                    </x-ui.dialog>
                                </li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </section>

            {{-- Visionneuse --}}
            <aside class="lg:col-span-3">
                <div class="material-solid p-5">
                    <h2 class="text-section-title text-ink">Comment traiter un dossier</h2>
                    <ol class="mt-3 space-y-2 text-[13.5px] text-ink-2">
                        <li class="flex gap-2"><span class="font-semibold text-ink">1.</span> Ouvrez chaque pièce avec « Voir » : le fichier s’affiche dans un nouvel onglet.</li>
                        <li class="flex gap-2"><span class="font-semibold text-ink">2.</span> Approuvez si la pièce est lisible et au bon nom ; rejetez sinon, avec un motif clair.</li>
                        <li class="flex gap-2"><span class="font-semibold text-ink">3.</span> Quand toutes les pièces sont approuvées, l’élève peut soumettre son dossier : il passe alors à l’étape Inscription.</li>
                    </ol>
                    <x-ui.alert tone="info" class="mt-4">
                        Approuver ou rejeter une pièce ne change pas l’étape de l’élève : seul le dépôt complet fait avancer le parcours.
                    </x-ui.alert>
                </div>
            </aside>
        </div>
    @endif
</x-app-layout>
