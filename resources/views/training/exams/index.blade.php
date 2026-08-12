<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Examens
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Nouvel examen</div>
                <form method="POST" action="{{ route('training.exams.store') }}" class="grid grid-cols-4 gap-3 items-end">
                    @csrf
                    <div>
                        <x-input-label for="student_id" value="Élève" />
                        <select id="student_id" name="student_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm block mt-1 w-full">
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}">{{ $student->fullName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="type" value="Type" />
                        <select id="type" name="type" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm block mt-1 w-full">
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
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr><th class="px-4 py-3">Élève</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Résultat</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($exams as $exam)
                            <tr>
                                <td class="px-4 py-3">{{ $exam->student->fullName() }}</td>
                                <td class="px-4 py-3">{{ $exam->type->label() }}</td>
                                <td class="px-4 py-3">{{ $exam->exam_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('training.exams.update', $exam) }}" class="flex gap-1">
                                        @csrf @method('PATCH')
                                        <select name="result" class="text-xs border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md" onchange="this.form.submit()">
                                            @foreach (\App\Domain\Training\Enums\ExamResult::cases() as $case)
                                                <option value="{{ $case->value }}" @selected($exam->result === $case)>{{ $case->label() }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Aucun examen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            {{ $exams->links() }}
        </div>
    </div>
</x-app-layout>
