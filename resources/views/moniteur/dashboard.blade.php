<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Espace moniteur
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 flex flex-wrap gap-6 text-sm">
                <a href="{{ route('moniteur.agenda') }}" class="text-indigo-600 dark:text-indigo-400 underline">
                    Mon agenda &rarr;
                </a>
                <a href="{{ route('students.index') }}" class="text-indigo-600 dark:text-indigo-400 underline">
                    Mes élèves &rarr;
                </a>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 text-gray-600 dark:text-gray-300">
                La feuille de route détaillée arrive avec une prochaine phase.
            </div>
        </div>
    </div>
</x-app-layout>
