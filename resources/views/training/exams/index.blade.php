<x-app-layout>
    <x-slot name="header">Examens</x-slot>

    <div class="py-6 space-y-5 max-w-4xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-card>
            <div class="text-sm font-semibold text-content mb-3">Nouvel examen</div>
            <form id="exams-create-form" method="POST" action="{{ route('training.exams.store') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
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
                            <th class="px-5 py-3 font-medium">Résultat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        @forelse ($exams as $exam)
                            <tr class="hover:bg-surface-elevated/60 transition">
                                <td class="px-5 py-3 text-content font-medium">{{ $exam->student->fullName() }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $exam->type->label() }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $exam->exam_date->format('d/m/Y') }}</td>
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
                            </tr>
                        @empty
                            <x-empty-table-row
                                colspan="4"
                                title="Aucun examen planifié."
                                message="Planifiez un examen pour un élève afin de suivre son résultat."
                                action="#exams-create-form"
                                action-label="Planifier un examen"
                            />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        {{ $exams->links() }}
    </div>
</x-app-layout>
