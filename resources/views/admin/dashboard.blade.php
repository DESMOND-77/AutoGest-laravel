<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Tableau de bord
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Élèves</div>
                    <div class="text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $totalStudents }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Anciens élèves</div>
                    <div class="text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $formerStudents }}</div>
                </div>
            </div>

            <a href="{{ route('students.index') }}" class="inline-block text-indigo-600 dark:text-indigo-400 underline text-sm">
                Voir la liste des élèves &rarr;
            </a>
        </div>
    </div>
</x-app-layout>
