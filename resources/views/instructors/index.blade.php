<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Moniteurs
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">{{ session('status') }}</div>
            @endif

            @can('create', \App\Domain\Instructors\Models\Instructor::class)
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Nouveau moniteur</div>
                    <form method="POST" action="{{ route('instructors.store') }}" class="grid grid-cols-4 gap-3 items-end">
                        @csrf
                        <div>
                            <x-input-label for="user_id" value="Utilisateur (id)" />
                            <x-text-input id="user_id" type="number" name="user_id" class="block mt-1 w-full" required />
                        </div>
                        <div>
                            <x-input-label for="license_number" value="N° agrément" />
                            <x-text-input id="license_number" name="license_number" class="block mt-1 w-full" />
                        </div>
                        <div>
                            <x-input-label for="hire_date" value="Date d'embauche" />
                            <x-text-input id="hire_date" type="date" name="hire_date" class="block mt-1 w-full" />
                        </div>
                        <div>
                            <x-primary-button>Ajouter</x-primary-button>
                        </div>
                    </form>
                </div>
            @endcan

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Nom</th>
                            <th class="px-4 py-3">N° agrément</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Embauche</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($instructors as $instructor)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('instructors.show', $instructor) }}" class="font-medium text-indigo-600 dark:text-indigo-400">
                                        {{ $instructor->user->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">{{ $instructor->license_number ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $instructor->status->label() }}</td>
                                <td class="px-4 py-3">{{ optional($instructor->hire_date)->format('d/m/Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Aucun moniteur.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
