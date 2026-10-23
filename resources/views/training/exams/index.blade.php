@php
    $pageExams = $exams->getCollection();
    $stats = [
        'pending' => $pageExams->filter(fn ($exam) => $exam->result?->value === 'pending')->count(),
        'passed' => $pageExams->filter(fn ($exam) => $exam->result?->value === 'passed')->count(),
        'failed' => $pageExams->filter(fn ($exam) => $exam->result?->value === 'failed')->count(),
    ];
    $types = collect(\App\Domain\Training\Enums\ExamType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all();
    $results = collect(\App\Domain\Training\Enums\ExamResult::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->all();
@endphp

<x-app-layout :title="'Examens'">
    <x-layout.page-header
        title="Examens"
        description="Planifiez les examens du code et de la conduite, puis saisissez les résultats."
        :breadcrumbs="[['label' => 'Examens']]"
    >
        <x-slot:actions>
            @can('create', \App\Domain\Training\Models\Exam::class)
                <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-exam' })">Nouvel examen</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        <div class="grid gap-3 sm:grid-cols-3">
            <x-data.kpi label="À venir / en attente" :value="$stats['pending']" icon="calendar" />
            <x-data.kpi label="Réussis (page)" :value="$stats['passed']" icon="trophy" trend="up" />
            <x-data.kpi label="Échecs (page)" :value="$stats['failed']" icon="circle-x" trend="down" />
        </div>

        <x-ui.card variant="solid" padding="p-0">
            @if ($exams->isEmpty())
                <x-ui.empty-state icon="award" title="Aucun examen enregistré" description="Enregistrez les convocations et saisissez les résultats pour suivre le taux de réussite." class="py-10">
                    <x-slot:actions>
                        @can('create', \App\Domain\Training\Models\Exam::class)
                            <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-exam' })">Nouvel examen</x-ui.button>
                        @endcan
                    </x-slot:actions>
                </x-ui.empty-state>
            @else
                <div class="px-1 pb-1">
                    <x-data.table caption="Liste des examens">
                        <thead>
                            <tr>
                                <th scope="col">Élève</th>
                                <th scope="col">Type</th>
                                <th scope="col">Date</th>
                                <th scope="col">Lieu / inspecteur</th>
                                <th scope="col">Résultat</th>
                                <th scope="col"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($exams as $exam)
                                <tr>
                                    <td data-label="Élève">
                                        @if ($exam->student)
                                            <a href="{{ route('students.show', $exam->student) }}" class="text-sm font-medium text-ink hover:text-accent">{{ $exam->student->fullName() }}</a>
                                        @else
                                            <span class="text-sm text-ink-3">—</span>
                                        @endif
                                    </td>
                                    <td data-label="Type">{{ $exam->type?->label() }}</td>
                                    <td data-label="Date"><x-ui.date :value="$exam->exam_date" /></td>
                                    <td data-label="Lieu / inspecteur">
                                        <span class="block text-[13px] text-ink-2">{{ $exam->location ?: '—' }}</span>
                                        @if ($exam->inspector)
                                            <span class="block text-[12px] text-ink-3">{{ $exam->inspector }}</span>
                                        @endif
                                    </td>
                                    <td data-label="Résultat"><x-ui.status :value="$exam->result" /></td>
                                    <td data-label="">
                                        <div class="flex justify-end">
                                            @can('update', $exam)
                                                <x-ui.button size="sm" variant="ghost" icon="pencil"
                                                    x-on:click="$dispatch('open-dialog', { name: 'result-{{ $exam->id }}' })">Résultat</x-ui.button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>

                                @can('update', $exam)
                                    <x-ui.dialog name="result-{{ $exam->id }}" title="Saisir le résultat" :description="'Examen du '.($exam->exam_date?->format('d/m/Y') ?? '—').' — '.($exam->student?->fullName() ?? '')">
                                        <form method="POST" action="{{ route('training.exams.update', $exam) }}" class="space-y-4">
                                            @csrf
                                            @method('PATCH')
                                            <x-form.field name="result" label="Résultat" required>
                                                <x-form.select name="result" :options="$results" :selected="$exam->result?->value" />
                                            </x-form.field>
                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <x-form.field name="fault_count" label="Nombre de fautes" optional>
                                                    <x-form.input name="fault_count" type="number" min="0" :value="$exam->fault_count" />
                                                </x-form.field>
                                            </div>
                                            <x-form.field name="comment" label="Commentaire" optional>
                                                <x-form.textarea name="comment" :value="$exam->comment" />
                                            </x-form.field>
                                            <div class="flex justify-end gap-2">
                                                <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'result-{{ $exam->id }}' })">Annuler</x-ui.button>
                                                <x-ui.button type="submit">Enregistrer</x-ui.button>
                                            </div>
                                        </form>
                                    </x-ui.dialog>
                                @endcan
                            @endforeach
                        </tbody>
                    </x-data.table>
                </div>
                <x-ui.pagination :paginator="$exams" label="Pagination des examens" />
            @endif
        </x-ui.card>
    </div>

    @can('create', \App\Domain\Training\Models\Exam::class)
        <x-ui.dialog name="new-exam" title="Nouvel examen">
            <x-form.errors />
            <form method="POST" action="{{ route('training.exams.store') }}" class="space-y-4">
                @csrf
                <x-form.field name="student_id" label="Élève" required>
                    <x-form.select name="student_id" :options="$students->mapWithKeys(fn ($s) => [$s->id => $s->fullName()])->all()" placeholder="Choisir un élève" required />
                </x-form.field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="type" label="Type d’examen" required>
                        <x-form.select name="type" :options="$types" :selected="'code'" />
                    </x-form.field>
                    <x-form.field name="exam_date" label="Date" required>
                        <x-form.input name="exam_date" type="date" :value="now()->addWeek()->format('Y-m-d')" required />
                    </x-form.field>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="location" label="Lieu" optional>
                        <x-form.input name="location" placeholder="Centre d’examen" />
                    </x-form.field>
                    <x-form.field name="inspector" label="Inspecteur" optional>
                        <x-form.input name="inspector" />
                    </x-form.field>
                </div>
                <x-form.field name="comment" label="Note interne" optional>
                    <x-form.textarea name="comment" />
                </x-form.field>
                <div class="flex justify-end gap-2">
                    <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'new-exam' })">Annuler</x-ui.button>
                    <x-ui.button type="submit">Enregistrer l’examen</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>
    @endcan
</x-app-layout>
