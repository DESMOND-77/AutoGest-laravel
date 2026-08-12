<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Mon espace
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 flex flex-wrap gap-6 text-sm">
                <a href="{{ route('eleve.planning') }}" class="text-indigo-600 dark:text-indigo-400 underline">
                    Mon planning &rarr;
                </a>
                <a href="{{ route('quiz.play') }}" class="text-indigo-600 dark:text-indigo-400 underline">
                    Entraînement au code &rarr;
                </a>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 text-gray-600 dark:text-gray-300">
                Votre progression et vos paiements arrivent avec une prochaine phase.
            </div>
        </div>
    </div>
</x-app-layout>
