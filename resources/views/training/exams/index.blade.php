<x-app-layout>
    <x-slot name="header">Examens</x-slot>

    <div class="py-6 space-y-5 max-w-4xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-card>
            <div class="text-sm font-semibold text-content mb-3">Nouvel examen</div>
            <form id="exams-create-form" method="POST" action="{{ route('training.exams.store') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                @csrf
                <div>
                    <x-input-label for="student_id" value="Élève" />
                    <select id="student_id" name="student_id" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->fullName() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="type" value="Type" />
                    <select id="type" name="type" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        @foreach (\App\Domain\Training\Enums\ExamType::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="exam_date" value="Date" />
                    <x-text-input id="exam_date" type="date" name="exam_date" class="block mt-1 w-full" required />
                </div>
                <div>
                    <x-input-label for="location" value="Lieu" />
                    <x-text-input id="location" name="location" class="block mt-1 w-full" />
                </div>
                <div>
                    <x-input-label for="inspector" value="Inspecteur" />
                    <x-text-input id="inspector" name="inspector" class="block mt-1 w-full" />
                </div>
                <div>
                    <x-primary-button>Planifier</x-primary-button>
                </div>
            </form>
        </x-card>

        <x-card :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-content-muted">
                        <tr>
                            <th class="px-5 py-3 font-medium">Élève</th>
                            <th class="px-5 py-3 font-medium">Type</th>
                            <th class="px-5 py-3 font-medium">Date</th>
                            <th class="px-5 py-3 font-medium">Lieu</th>
                            <th class="px-5 py-3 font-medium">Inspecteur</th>
                            <th class="px-5 py-3 font-medium">Résultat</th>
                            <th class="px-5 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    @forelse ($exams as $exam)
                        <tbody x-data="{ open: false }" class="divide-y divide-border/60 border-t border-border/60 first:border-t-0">
                            <tr class="hover:bg-surface-elevated/60 transition">
                                <td class="px-5 py-3 text-content font-medium">{{ $exam->student->fullName() }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $exam->type->label() }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $exam->exam_date->format('d/m/Y') }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $exam->location ?? '—' }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $exam->inspector ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <form method="POST" action="{{ route('training.exams.update', $exam) }}" class="flex gap-1">
                                        @csrf @method('PATCH')
                                        <select name="result" class="text-xs rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0" onchange="this.form.submit()">
                                            @foreach (\App\Domain\Training\Enums\ExamResult::cases() as $case)
                                                <option value="{{ $case->value }}" @selected($exam->result === $case)>{{ $case->label() }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    <button type="button" @click="open = !open" class="text-xs text-content-secondary hover:text-primary transition" :aria-expanded="open.toString()">
                                        <span x-text="open ? 'Fermer' : 'Détails'"></span>
                                    </button>
                                </td>
                            </tr>
                            <tr x-show="open" x-cloak>
                                <td colspan="7" class="px-5 py-4 bg-surface-inset/40">
                                    <div class="text-xs text-content-secondary mb-2">
                                        <span class="font-medium text-content">Fautes :</span> {{ $exam->fault_count ?? '—' }}
                                        <span class="mx-2">·</span>
                                        <span class="font-medium text-content">Commentaire :</span> {{ $exam->comment ?? '—' }}
                                    </div>
                                    <form method="POST" action="{{ route('training.exams.update', $exam) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="result" value="{{ $exam->result->value }}" />
                                        <div>
                                            <x-input-label for="fault_count-{{ $exam->id }}" value="Nombre de fautes" />
                                            <x-text-input id="fault_count-{{ $exam->id }}" type="number" min="0" name="fault_count" class="block mt-1 w-full" value="{{ $exam->fault_count }}" />
                                        </div>
                                        <div class="sm:col-span-2">
                                            <x-input-label for="comment-{{ $exam->id }}" value="Commentaire" />
                                            <textarea id="comment-{{ $exam->id }}" name="comment" rows="2" class="w-full rounded-ui-md border-0 bg-surface px-3.5 py-2.5 text-sm text-content shadow-inset placeholder:text-content-muted focus:outline-none focus:shadow-inset-focus mt-1">{{ $exam->comment }}</textarea>
                                        </div>
                                        <div class="sm:col-span-3">
                                            <x-primary-button>Enregistrer</x-primary-button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        </tbody>
                    @empty
                        <tbody>
                            <x-empty-table-row
                                colspan="7"
                                title="Aucun examen planifié."
                                message="Planifiez un examen pour un élève afin de suivre son résultat."
                                action="#exams-create-form"
                                action-label="Planifier un examen"
                            />
                        </tbody>
                    @endforelse
                </table>
            </div>
        </x-card>

        {{ $exams->links() }}
    </div>
</x-app-layout>
