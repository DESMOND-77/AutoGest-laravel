<x-app-layout>
    <x-slot name="header">{{ $student->fullName() }}</x-slot>

    <div class="py-6 space-y-5 max-w-5xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif
        @if ($errors->any())
            <x-alert variant="danger">{{ $errors->first() }}</x-alert>
        @endif

        {{-- Profile header --}}
        <x-card>
            <div class="flex flex-col sm:flex-row sm:items-center gap-5">
                <span
                    class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary text-xl font-semibold">
                    {{ mb_substr($student->first_name, 0, 1) }}{{ mb_substr($student->last_name, 0, 1) }}
                </span>

                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-lg font-semibold text-content">{{ $student->fullName() }}</h1>
                        <x-badge variant="info">{{ $student->lifecycle_stage->label() }}</x-badge>
                    </div>
                    <p class="text-sm text-content-secondary mt-0.5">
                        {{ $student->license_category->value }} &middot; {{ $student->course_type->label() }}
                        @if ($student->phone)
                            &middot; {{ $student->phone }}
                        @endif
                    </p>

                    @php
                        $allStages = \App\Domain\Students\Enums\LifecycleStage::cases();
                        $stageIndex = array_search($student->lifecycle_stage, $allStages, true);
                        $progressPercent = (int) round(($stageIndex / (count($allStages) - 1)) * 100);
                    @endphp
                    <div class="mt-3 max-w-sm">
                        <div class="flex items-center justify-between text-xs text-content-muted mb-1">
                            <span>Progression du parcours</span>
                            <span>{{ $progressPercent }}%</span>
                        </div>
                        <div class="bg-surface-inset rounded-full h-2 overflow-hidden">
                            <div class="bg-primary h-2 rounded-full" style="width: {{ max(2, $progressPercent) }}%">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 sm:flex-col sm:items-end shrink-0">
                    @can('evaluate', $student)
                        <a href="{{ route('training.evaluation.show', $student) }}"
                            class="text-sm text-content-secondary hover:text-primary transition">Évaluer</a>
                    @endcan
                    @can('create', \App\Domain\Finance\Models\Invoice::class)
                        <a href="{{ route('finance.invoices.create', $student) }}"
                            class="text-sm text-content-secondary hover:text-primary transition">Nouvelle facture</a>
                    @endcan
                    @can('update', $student)
                        <a href="{{ route('students.edit', $student) }}"
                            class="text-sm text-content-secondary hover:text-primary transition">Modifier</a>
                    @endcan
                    @can('update', $student)
                        @if (! $student->user_id)
                            <form method="POST" action="{{ route('students.create-account', $student) }}">
                                @csrf
                                <button type="submit" class="text-sm text-content-secondary hover:text-primary transition">Créer un compte</button>
                            </form>
                        @endif
                    @endcan
                </div>
            </div>
        </x-card>

        <x-tabs :tabs="[
            'general' => 'Vue générale',
            'informations' => 'Informations',
            'formation' => 'Formation',
            'planning' => 'Planning',
            'paiements' => 'Paiements',
            'documents' => 'Documents',
            'examens' => 'Examens',
            'historique' => 'Historique',
        ]">
            {{-- Vue générale --}}
            <div x-show="tab === 'general'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <x-card>
                    <div class="text-sm font-semibold text-content mb-3">Coordonnées</div>
                    <dl class="space-y-2 text-sm">
                        <div class="flex items-center gap-2"><x-icon name="phone"
                                class="w-4 h-4 text-content-muted" /><span
                                class="text-content">{{ $student->phone ?? '-' }}</span></div>
                        <div class="flex items-center gap-2"><x-icon name="envelope"
                                class="w-4 h-4 text-content-muted" /><span
                                class="text-content">{{ $student->email ?? '-' }}</span></div>
                        <div class="flex items-center gap-2"><x-icon name="map-pin"
                                class="w-4 h-4 text-content-muted" /><span
                                class="text-content">{{ $student->address ?? '-' }}</span></div>
                    </dl>
                </x-card>
                <x-card>
                    <div class="text-sm font-semibold text-content mb-3">Dossier & Moniteur</div>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-content-secondary">Dossier</span><span
                                class="text-content font-medium">{{ $student->dossier_status->label() }}</span></div>
                        <div class="flex justify-between"><span class="text-content-secondary">Moniteur</span><span
                                class="text-content font-medium">{{ $student->instructor?->name ?? '-' }}</span></div>
                        <div class="flex justify-between"><span class="text-content-secondary">Inscrit le</span><span
                                class="text-content font-medium">{{ $student->registered_at?->format('d/m/Y') ?? '-' }}</span>
                        </div>
                    </dl>
                </x-card>
            </div>

            {{-- Informations --}}
            <div x-show="tab === 'informations'" x-cloak>
                <x-card>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <div><span class="text-content-muted">Nom</span>
                            <p class="text-content font-medium">{{ $student->last_name }}</p>
                        </div>
                        <div><span class="text-content-muted">Prénom</span>
                            <p class="text-content font-medium">{{ $student->first_name }}</p>
                        </div>
                        <div><span class="text-content-muted">Date de naissance</span>
                            <p class="text-content font-medium">{{ $student->birth_date?->format('d/m/Y') ?? '-' }}</p>
                        </div>
                        <div><span class="text-content-muted">Lieu de naissance</span>
                            <p class="text-content font-medium">{{ $student->birth_place ?? '-' }}</p>
                        </div>
                        <div><span class="text-content-muted">Téléphone</span>
                            <p class="text-content font-medium">{{ $student->phone ?? '-' }}</p>
                        </div>
                        <div><span class="text-content-muted">Téléphone secondaire</span>
                            <p class="text-content font-medium">{{ $student->phone_secondary ?? '-' }}</p>
                        </div>
                        <div><span class="text-content-muted">E-mail</span>
                            <p class="text-content font-medium">{{ $student->email ?? '-' }}</p>
                        </div>
                        <div><span class="text-content-muted">NEPH</span>
                            <p class="text-content font-medium">{{ $student->neph ?? '-' }}</p>
                        </div>
                        <div class="sm:col-span-2"><span class="text-content-muted">Adresse</span>
                            <p class="text-content font-medium">{{ $student->address ?? '-' }}</p>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- Formation --}}
            <div x-show="tab === 'formation'" x-cloak class="space-y-5">
                <x-card>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm mb-5">
                        <div><span class="text-content-muted">Catégorie</span>
                            <p class="text-content font-medium">{{ $student->license_category->value }}</p>
                        </div>
                        <div><span class="text-content-muted">Type de cours</span>
                            <p class="text-content font-medium">{{ $student->course_type->label() }}</p>
                        </div>
                        <div><span class="text-content-muted">Étape actuelle</span>
                            <p class="text-content font-medium">{{ $student->lifecycle_stage->label() }}</p>
                        </div>
                    </div>

                    <div class="border-t border-border/60 pt-4">
                        <ol class="space-y-1.5">
                            @foreach ($stages as $stage)
                                @php $done = $stage->value === $student->lifecycle_stage->value || array_search($stage, $allStages, true) < $stageIndex; @endphp
                                <li class="flex items-center gap-2 text-sm">
                                    <span @class([
                                        'flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold',
                                        'bg-primary text-primary-content' =>
                                            $stage->value === $student->lifecycle_stage->value,
                                        'bg-success/15 text-success' =>
                                            $done && $stage->value !== $student->lifecycle_stage->value,
                                        'bg-surface-inset text-content-muted' => !$done,
                                    ])>
                                        @if ($done && $stage->value !== $student->lifecycle_stage->value)
                                            &check;
                                        @else
                                            {{ $loop->iteration }}
                                        @endif
                                    </span>
                                    <span @class([
                                        'text-content font-medium' =>
                                            $stage->value === $student->lifecycle_stage->value,
                                        'text-content-secondary' =>
                                            $stage->value !== $student->lifecycle_stage->value,
                                    ])>
                                        {{ $stage->label() }}
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    </div>

                    @can('update', $student)
                        @php $next = $student->lifecycle_stage->allowedNextStages(); @endphp
                        @if (count($next))
                            <div class="flex flex-wrap gap-2 mt-5 pt-4 border-t border-border/60">
                                @foreach ($next as $stage)
                                    <form method="POST" action="{{ route('students.stage', $student) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="stage" value="{{ $stage->value }}">
                                        <button type="submit"
                                            class="inline-flex items-center gap-1 text-sm bg-surface-inset px-3 py-1.5 rounded-ui-md text-content hover:shadow-soft-sm transition">
                                            <x-icon name="chevron-right" class="w-4 h-4" /> {{ $stage->label() }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @endif
                    @endcan
                </x-card>
            </div>

            {{-- Planning --}}
            <div x-show="tab === 'planning'" x-cloak>
                <x-card :padded="false">
                    @php
                        $sessions = \App\Domain\Scheduling\Models\LessonSession::query()
                            ->where('student_id', $student->id)
                            ->with('instructor', 'vehicle')
                            ->orderByDesc('scheduled_date')
                            ->limit(10)
                            ->get();
                    @endphp
                    @if ($sessions->isEmpty())
                        <p class="text-sm text-content-muted p-6 text-center">Aucune séance planifiée pour cet élève.
                        </p>
                    @else
                        <ul class="divide-y divide-border/60">
                            @foreach ($sessions as $session)
                                <li class="px-5 py-3 flex items-center justify-between text-sm gap-3">
                                    <div class="min-w-0">
                                        <p class="text-content font-medium">
                                            {{ $session->scheduled_date->format('d/m/Y') }} &middot;
                                            {{ $session->starts_at }}–{{ $session->ends_at }}</p>
                                        <p class="text-content-muted text-xs">{{ $session->type->label() }} &middot;
                                            {{ $session->instructor?->name ?? '-' }} &middot;
                                            {{ $session->vehicle?->plate ?? '-' }}</p>
                                    </div>
                                    <x-badge :variant="$session->presence?->value === 'present' ? 'success' : ($session->presence?->value === 'absent' ? 'danger' : 'neutral')">
                                        {{ $session->presence?->label() ?? 'Prévue' }}
                                    </x-badge>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="px-5 py-3 border-t border-border/60">
                        <a href="{{ route('scheduling.index', ['student_id' => $student->id]) }}"
                            class="inline-flex items-center gap-1 text-xs text-primary hover:underline">
                            Voir le planning complet <x-icon name="chevron-right" class="w-3 h-3" />
                        </a>
                    </div>
                </x-card>
            </div>

            {{-- Paiements --}}
            <div x-show="tab === 'paiements'" x-cloak>
                <x-card :padded="false">
                    @php
                        $invoices = \App\Domain\Finance\Models\Invoice::query()
                            ->where('student_id', $student->id)
                            ->latest('issued_at')
                            ->get();
                    @endphp
                    @if ($invoices->isEmpty())
                        <p class="text-sm text-content-muted p-6 text-center">Aucune facture pour cet élève.</p>
                    @else
                        <ul class="divide-y divide-border/60">
                            @foreach ($invoices as $invoice)
                                <li class="px-5 py-3 flex items-center justify-between text-sm gap-3">
                                    <div class="min-w-0">
                                        <a href="{{ route('finance.invoices.show', $invoice) }}"
                                            class="text-content font-medium hover:text-primary transition">{{ $invoice->label }}</a>
                                        <p class="text-content-muted text-xs">
                                            {{ $invoice->issued_at?->format('d/m/Y') }} &middot;
                                            {{ number_format((float) $invoice->amount_paid, 0, ',', ' ') }} /
                                            {{ number_format((float) $invoice->amount_due, 0, ',', ' ') }} FCFA</p>
                                    </div>
                                    <x-badge :variant="$invoice->status->value === 'paid' ? 'success' : ($invoice->status->value === 'unpaid' ? 'danger' : 'warning')">
                                        {{ $invoice->status->label() }}
                                    </x-badge>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            </div>

            {{-- Documents --}}
            <div x-show="tab === 'documents'" x-cloak class="space-y-5">
                <x-card>
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm font-semibold text-content">Dossier administratif</div>
                        <x-badge variant="info">{{ $student->dossier_status->label() }}</x-badge>
                    </div>

                    <ol class="flex flex-wrap gap-1.5">
                        @foreach (\App\Domain\Students\Enums\DossierStatus::cases() as $status)
                            <li @class([
                                'px-2.5 py-1 rounded-ui-md text-xs font-medium',
                                'bg-primary text-primary-content' => $status === $student->dossier_status,
                                'bg-surface-inset text-content-secondary' => $status !== $student->dossier_status,
                            ])>
                                {{ $status->label() }}
                            </li>
                        @endforeach
                    </ol>

                    @can('update', $student)
                        @php $nextDossierStatuses = $student->dossier_status->allowedNextStages(); @endphp
                        @if (count($nextDossierStatuses))
                            <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-border/60">
                                @foreach ($nextDossierStatuses as $status)
                                    <form method="POST" action="{{ route('students.dossier-status', $student) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="dossier_status" value="{{ $status->value }}">
                                        <button type="submit"
                                            class="inline-flex items-center gap-1 text-sm bg-surface-inset px-3 py-1.5 rounded-ui-md text-content hover:shadow-soft-sm transition">
                                            <x-icon name="chevron-right" class="w-4 h-4" /> {{ $status->label() }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @endif
                    @endcan
                </x-card>

                <x-card>
                    @php
                        $documents = \App\Domain\Documents\Models\Document::query()
                            ->where('documentable_type', $student->getMorphClass())
                            ->where('documentable_id', $student->id)
                            ->where('is_current', true)
                            ->with('requiredDocumentType')
                            ->latest()
                            ->get();
                    @endphp

                    <ul class="text-sm divide-y divide-border/60 mb-4">
                        @forelse ($documents as $document)
                            <li class="py-2.5 flex justify-between items-center gap-3">
                                <span class="text-content min-w-0">
                                    {{ $document->requiredDocumentType?->label ?? $document->type->label() }} -
                                    {{ $document->original_name }} (v{{ $document->version }})
                                    @if ($document->required_document_type_id)
                                        <x-badge :variant="$document->review_status->value === 'approved' ? 'success' : ($document->review_status->value === 'rejected' ? 'danger' : 'warning')" class="ml-1">
                                            {{ $document->review_status->label() }}
                                        </x-badge>
                                    @endif
                                </span>
                                <a href="{{ route('documents.show', $document) }}"
                                    class="text-xs text-primary hover:underline shrink-0">Visualiser</a>
                            </li>
                        @empty
                            <li class="py-2.5 text-content-muted">Aucun document.</li>
                        @endforelse
                    </ul>

                    @can('create', \App\Domain\Documents\Models\Document::class)
                        @php
                            $requiredDocumentTypes = \App\Domain\Students\Models\RequiredDocumentType::query()
                                ->active()
                                ->ordered()
                                ->get();
                        @endphp
                        @if ($requiredDocumentTypes->isEmpty())
                            <p class="text-sm text-content-muted pt-2">
                                Aucune pièce requise n'est configurée  - voir Paramètres &gt; Pièces
                                requises.
                            </p>
                        @else
                            <form method="POST" action="{{ route('students.documents.store', $student) }}"
                                enctype="multipart/form-data"
                                class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end pt-2">
                                @csrf
                                <select name="required_document_type_id"
                                    class="rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm block w-full">
                                    @foreach ($requiredDocumentTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->label }}</option>
                                    @endforeach
                                </select>
                                <input type="file" name="file" class="text-sm text-content" required>
                                <x-primary-button>Déposer</x-primary-button>
                            </form>
                        @endif
                    @endcan
                </x-card>
            </div>

            {{-- Examens --}}
            <div x-show="tab === 'examens'" x-cloak>
                <x-card :padded="false">
                    @php
                        $exams = \App\Domain\Training\Models\Exam::query()
                            ->where('student_id', $student->id)
                            ->orderByDesc('exam_date')
                            ->get();
                    @endphp
                    @if ($exams->isEmpty())
                        <p class="text-sm text-content-muted p-6 text-center">Aucun examen enregistré.</p>
                    @else
                        <ul class="divide-y divide-border/60">
                            @foreach ($exams as $exam)
                                <li class="px-5 py-3 flex items-center justify-between text-sm gap-3">
                                    <div class="min-w-0">
                                        <p class="text-content font-medium">{{ $exam->type->label() }}</p>
                                        <p class="text-content-muted text-xs">{{ $exam->exam_date->format('d/m/Y') }}
                                        </p>
                                    </div>
                                    <x-badge :variant="$exam->result->value === 'passed' ? 'success' : ($exam->result->value === 'failed' ? 'danger' : 'neutral')">
                                        {{ $exam->result->label() }}
                                    </x-badge>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            </div>

            {{-- Historique --}}
            <div x-show="tab === 'historique'" x-cloak>
                <x-card :padded="false">
                    @php
                        $logs = \App\Domain\Audit\Models\AuditLog::query()
                            ->where('auditable_type', $student->getMorphClass())
                            ->where('auditable_id', $student->id)
                            ->with('user')
                            ->latest()
                            ->limit(20)
                            ->get();
                    @endphp
                    @if ($logs->isEmpty())
                        <p class="text-sm text-content-muted p-6 text-center">Aucun historique disponible.</p>
                    @else
                        <ul class="divide-y divide-border/60">
                            @foreach ($logs as $log)
                                <li class="px-5 py-3 text-sm">
                                    <p class="text-content">{{ $log->action }} <span class="text-content-muted">-
                                            {{ $log->user?->name ?? 'Système' }}</span></p>
                                    <p class="text-content-muted text-xs">{{ $log->created_at->format('d/m/Y H:i') }}
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </x-card>
            </div>
        </x-tabs>
    </div>
</x-app-layout>
