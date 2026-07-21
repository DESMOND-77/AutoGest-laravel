<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Élèves
            </h2>
            @can('create', \App\Domain\Students\Models\Student::class)
                <a href="{{ route('students.create') }}" class="text-sm bg-indigo-600 text-white px-3 py-1.5 rounded-md">
                    + Nouvel élève
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Nom</th>
                            <th class="px-4 py-3">Catégorie</th>
                            <th class="px-4 py-3">Étape</th>
                            <th class="px-4 py-3">Dossier</th>
                            <th class="px-4 py-3">Moniteur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($students as $student)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('students.show', $student) }}" class="font-medium text-indigo-600 dark:text-indigo-400">
                                        {{ $student->fullName() }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">{{ $student->license_category->value }}</td>
                                <td class="px-4 py-3">{{ $student->lifecycle_stage->label() }}</td>
                                <td class="px-4 py-3">{{ $student->dossier_status->label() }}</td>
                                <td class="px-4 py-3">{{ $student->instructor?->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">Aucun élève.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $students->links() }}
        </div>
    </div>
</x-app-layout>
