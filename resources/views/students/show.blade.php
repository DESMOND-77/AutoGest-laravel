@php
    $sessions = \App\Domain\Scheduling\Models\LessonSession::query()
        ->where('student_id', $student->id)
        ->with('instructor', 'vehicle')
        ->orderByDesc('scheduled_date')
        ->limit(10)
        ->get();
    $invoices = \App\Domain\Finance\Models\Invoice::query()
        ->where('student_id', $student->id)
        ->latest('issued_at')
        ->get();
    $exams = \App\Domain\Training\Models\Exam::query()
        ->where('student_id', $student->id)
        ->orderByDesc('exam_date')
        ->get();
    $documents = $student->documents()->where('is_current', true)->latest()->get();
    $logs = \App\Domain\Audit\Models\AuditLog::query()
        ->where('auditable_type', $student->getMorphClass())
        ->where('auditable_id', $student->id)
        ->with('user')
        ->latest()
        ->limit(20)
        ->get();
    $balance = $invoices->sum(fn ($invoice) => $invoice->balanceDue());
    $nextStages = $student->lifecycle_stage?->allowedNextStages() ?? [];
    $tabs = [
        ['label' => 'Vue générale', 'href' => '#resume'],
        ['label' => 'Dossier', 'href' => '#dossier'],
        ['label' => 'Planning', 'href' => '#planning'],
        ['label' => 'Finance', 'href' => '#finance'],
        ['label' => 'Examens', 'href' => '#examens'],
        ['label' => 'Historique', 'href' => '#historique'],
    ];
@endphp

<x-app-layout :title="$student->fullName()">
    <x-layout.page-header
        :title="$student->fullName()"
        :breadcrumbs="[['label' => 'Élèves', 'href' => route('students.index')], ['label' => $student->fullName()]]"
        :description="($student->license_category?->label() ?? 'Permis').' · '.($student->course_type?->label() ?? 'Formule').' · inscrit le '.($student->registered_at?->format('d/m/Y') ?? '—')"
    >
        <x-slot:actions>
            <x-ui.status :value="$student->lifecycle_stage" />
            @can('update', $student)
                <x-ui.button variant="secondary" icon="route" x-on:click="$dispatch('open-dialog', { name: 'stage-{{ $student->id }}' })">Changer d’étape</x-ui.button>
            @endcan
            @can('create', \App\Domain\Finance\Models\Invoice::class)
                <x-ui.button :href="route('finance.invoices.create', $student)" variant="secondary" icon="receipt">Nouvelle facture</x-ui.button>
            @endcan
            @can('update', $student)
                @if ($student->documents_zip_path)
                    <x-ui.button :href="route('students.dossier-download', $student)" variant="secondary" icon="download">Dossier ZIP</x-ui.button>
                @endif
                <x-ui.button :href="route('students.edit', $student)" icon="pencil">Modifier</x-ui.button>
            @endcan
        </x-slot:actions>

    </x-layout.page-header>

    <x-domain.stage-stepper :stage="$student->lifecycle_stage" class="material-panel mb-4 p-4" />

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            {{-- Résumé --}}
            <x-ui.card id="resume" title="Vue générale" variant="solid">
                <x-data.description-list :items="[
                    ['label' => 'Téléphone', 'value' => $student->phone ?: '—'],
                    ['label' => 'Téléphone secondaire', 'value' => $student->phone_secondary ?: '—'],
                    ['label' => 'E-mail', 'value' => $student->email ?: '—'],
                    ['label' => 'NEPH', 'value' => $student->neph ?: '—'],
                    ['label' => 'Date de naissance', 'value' => $student->birth_date?->format('d/m/Y') ?? '—'],
                    ['label' => 'Lieu de naissance', 'value' => $student->birth_place ?: '—'],
                    ['label' => 'Adresse', 'value' => $student->address ?: '—'],
                    ['label' => 'Moniteur', 'value' => $student->instructor?->name ?? '—'],
                ]" />
            </x-ui.card>

            {{-- Dossier --}}
            <x-ui.card id="dossier" title="Dossier" description="Pièces déposées et statut de validation." variant="solid" :padding="'p-0'">
                @if ($documents->isEmpty())
                    <x-ui.empty-state icon="folder" title="Aucune pièce déposée" description="Les pièces apparaissent ici dès que l’élève les dépose depuis son espace." class="py-8" />
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($documents as $document)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-ink">{{ $document->requiredDocumentType?->label ?? $document->type?->label() }}</p>
                                    <p class="truncate text-[12px] text-ink-3">{{ $document->original_name }} · v{{ $document->version }}</p>
                                    @if ($document->rejection_reason)
                                        <p class="mt-1 text-[12px] font-medium text-danger">Motif : {{ $document->rejection_reason }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-ui.status :value="$document->review_status" />
                                    <x-ui.button :href="route('documents.show', $document)" size="sm" variant="ghost" icon="eye" label="Consulter la pièce" />
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
                @can('create', \App\Domain\Documents\Models\Document::class)
                    @php
                        $requiredDocumentTypes = \App\Domain\Students\Models\RequiredDocumentType::query()->active()->ordered()->get();
                    @endphp
                    <div class="border-t border-line px-5 py-4">
                        @if ($requiredDocumentTypes->isEmpty())
                            <p class="text-[13px] text-ink-2">Aucune pièce requise n'est configurée - voir Paramètres &gt; Pièces requises.</p>
                        @else
                            <form method="POST" action="{{ route('students.documents.store', $student) }}" enctype="multipart/form-data" class="grid items-end gap-3 sm:grid-cols-3">
                                @csrf
                                <x-form.select name="required_document_type_id" :options="$requiredDocumentTypes->pluck('label', 'id')->all()" />
                                <x-form.file-drop name="file" label="Pièce à déposer" />
                                <x-ui.button type="submit" icon="upload">Déposer</x-ui.button>
                            </form>
                        @endif
                    </div>
                @endcan
                <div class="border-t border-line px-5 py-3">
                    <a href="{{ route('dossiers.index') }}" class="text-[13px] font-medium text-accent hover:underline">Ouvrir la file de validation</a>
                </div>
            </x-ui.card>

            {{-- Planning --}}
            <x-ui.card id="planning" title="Séances" description="Les 10 dernières séances." variant="solid" :padding="'p-0'">
                @if ($sessions->isEmpty())
                    <x-ui.empty-state icon="calendar" title="Aucune séance" description="Planifiez une séance depuis le planning pour démarrer la formation pratique." class="py-8" />
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($sessions as $session)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-2.5">
                                <div class="min-w-0">
                                    <p class="text-[13.5px] font-medium text-ink">
                                        <x-ui.date :value="$session->scheduled_date" /> · {{ \Illuminate\Support\Carbon::parse($session->starts_at)->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($session->ends_at)->format('H:i') }}
                                    </p>
                                    <p class="text-[12px] text-ink-3">{{ $session->type?->label() }} · {{ $session->instructor?->name ?? '—' }} · {{ $session->vehicle?->plate ?? 'sans véhicule' }}</p>
                                </div>
                                <x-ui.status :value="$session->presence" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            {{-- Finance --}}
            <x-ui.card id="finance" title="Factures et paiements" :description="'Solde restant : '.number_format((float) $balance, 0, ',', ' ').' FCFA'" variant="solid" :padding="'p-0'">
                @if ($invoices->isEmpty())
                    <x-ui.empty-state icon="receipt" title="Aucune facture" description="Créez une facture pour enregistrer les paiements de cet élève." class="py-8" />
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($invoices as $invoice)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-ink">{{ $invoice->label ?: 'Facture #'.$invoice->id }}</p>
                                    <p class="text-[12px] text-ink-3">
                                        <x-ui.date :value="$invoice->issued_at" /> · payé <x-ui.money :amount="$invoice->amount_paid" /> sur <x-ui.money :amount="$invoice->amount_due" />
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <x-ui.status :value="$invoice->status" />
                                    <x-ui.button :href="route('finance.invoices.show', $invoice)" size="sm" variant="ghost" icon-right="chevron-right">Ouvrir</x-ui.button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            {{-- Examens --}}
            <x-ui.card id="examens" title="Examens" variant="solid" :padding="'p-0'">
                @if ($exams->isEmpty())
                    <x-ui.empty-state icon="award" title="Aucun examen enregistré" description="Les résultats saisis par l’administration apparaîtront ici." class="py-8" />
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($exams as $exam)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-2.5">
                                <div class="min-w-0">
                                    <p class="text-[13.5px] font-medium text-ink">{{ $exam->type?->label() }} · <x-ui.date :value="$exam->exam_date" /></p>
                                    <p class="text-[12px] text-ink-3">{{ $exam->location ?: 'Lieu non précisé' }}{{ $exam->fault_count !== null ? ' · '.$exam->fault_count.' faute(s)' : '' }}</p>
                                </div>
                                <x-ui.status :value="$exam->result" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            {{-- Historique --}}
            <x-ui.card id="historique" title="Historique" description="Changements d’étape et actions d’audit." variant="solid">
                @php
                    $timeline = $logs->map(fn ($log) => [
                        'title' => str_replace('.', ' ', $log->action),
                        'description' => null,
                        'at' => $log->created_at,
                        'actor' => $log->user?->name,
                        'icon' => 'shield',
                        'tone' => 'accent',
                    ])->all();
                @endphp
                <x-data.timeline :items="$timeline" empty="Aucun événement enregistré." />
            </x-ui.card>
        </div>

        {{-- Colonne latérale --}}
        <aside class="space-y-4">
            <x-ui.card title="Résumé" variant="panel">
                <div class="space-y-3">
                    <div>
                        <p class="text-[12px] uppercase tracking-wide text-ink-3">Solde</p>
                        <p class="text-xl font-bold text-ink"><x-ui.money :amount="$balance" /></p>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[13px] text-ink-2">Dossier</span>
                        <x-ui.status :value="$student->dossier_status" />
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[13px] text-ink-2">Séances</span>
                        <span class="text-sm font-semibold text-ink">{{ $sessions->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[13px] text-ink-2">Pièces déposées</span>
                        <span class="text-sm font-semibold text-ink">{{ $documents->count() }}</span>
                    </div>
                </div>
            </x-ui.card>

            @if ($student->user_id === null)
                @can('update', $student)
                    <x-ui.card title="Compte élève" variant="solid">
                        <p class="text-[13px] text-ink-2">Cet élève n’a pas encore de compte pour consulter son dossier en ligne.</p>
                        <form method="POST" action="{{ route('students.create-account', $student) }}" class="mt-3">
                            @csrf
                            <x-ui.button type="submit" size="sm" variant="secondary" icon="user-plus">Créer son compte</x-ui.button>
                        </form>
                    </x-ui.card>
                @endcan
            @endif

            @can('update', $student)
                <form method="POST" action="{{ route('students.destroy', $student) }}">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="ghost" icon="trash-2" class="w-full text-danger"
                        x-on:click.prevent="$dispatch('open-confirm', { name: 'delete-student-{{ $student->id }}' })">
                        Supprimer l’élève
                    </x-ui.button>
                </form>
                <x-ui.confirm
                    name="delete-student-{{ $student->id }}"
                    title="Supprimer cet élève ?"
                    :consequence="'Le compte élève sera désactivé. Cette action est réservée aux cas de doublon ou de saisie erronée.'"
                    confirm-label="Supprimer l’élève"
                    :action="route('students.destroy', $student)"
                />
            @endcan
        </aside>
    </div>

    @can('update', $student)
        <x-ui.dialog name="stage-{{ $student->id }}" title="Changer l’étape du parcours" :description="'Étape actuelle : '.$student->lifecycle_stage?->label()">
            @if (count($nextStages) === 0)
                <x-ui.alert tone="info">Cette étape est terminale : aucune transition n’est possible.</x-ui.alert>
            @else
                <form method="POST" action="{{ route('students.stage', $student) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <fieldset class="space-y-2">
                        <legend class="text-sm font-medium text-ink">Transitions autorisées uniquement</legend>
                        @foreach ($nextStages as $next)
                            <x-form.radio-card name="stage" :value="$next->value" :title="$next->label()"
                                :description="in_array($next->value, ['dossier_setup', 'continuous_evaluation'], true) ? 'Retour en arrière : l’étape précédente sera réouverte.' : null" />
                        @endforeach
                    </fieldset>
                    <p class="text-[12.5px] text-ink-3">
                        Le changement d’étape est un repère de suivi, il ne crée ni facture ni séance.
                    </p>
                    <div class="flex justify-end gap-2">
                        <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'stage-{{ $student->id }}' })">Annuler</x-ui.button>
                        <x-ui.button type="submit">Enregistrer l’étape</x-ui.button>
                    </div>
                </form>
            @endif
        </x-ui.dialog>
    @endcan
</x-app-layout>
