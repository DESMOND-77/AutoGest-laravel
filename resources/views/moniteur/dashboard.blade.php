<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Espace moniteur
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 text-gray-600 dark:text-gray-300">
                L'agenda, la feuille de route et l'évaluation arrivent avec la
                phase Scheduling + Training de la migration.
            </div>

            <a href="{{ route('students.index') }}" class="inline-block text-indigo-600 dark:text-indigo-400 underline text-sm">
                Voir mes élèves &rarr;
            </a>
        </div>
    </div>
</x-app-layout>
