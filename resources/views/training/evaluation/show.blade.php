<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Évaluation — {{ $student->fullName() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3 mb-4">{{ session('status') }}</div>
            @endif

            @if ($skills->isEmpty())
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 text-gray-600 dark:text-gray-300">
                    Aucune compétence définie pour cet établissement.
                </div>
            @else
                <form method="POST" action="{{ route('training.evaluation.store', $student) }}">
                    @csrf
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($skills as $skill)
                            @php $current = $progress->get($skill->id)?->level?->value ?? 'not_started'; @endphp
                            <div class="p-4 flex items-center justify-between gap-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $skill->label }}</div>
                                    <div class="text-xs text-gray-500">{{ $skill->category }}</div>
                                </div>
                                <div class="flex gap-3 text-sm">
                                    @foreach (\App\Domain\Training\Enums\SkillLevel::cases() as $level)
                                        <label class="flex items-center gap-1">
                                            <input type="radio" name="levels[{{ $skill->id }}]" value="{{ $level->value }}" @checked($current === $level->value)>
                                            {{ $level->label() }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex justify-end mt-6">
                        <x-primary-button>Enregistrer l'évaluation</x-primary-button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
