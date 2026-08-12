<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Mon planning — semaine du {{ $week->format('d/m/Y') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (! $student)
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 text-gray-600 dark:text-gray-300">
                    Votre dossier est en cours de traitement.
                </div>
            @else
                <div class="flex gap-3 text-sm">
                    <a href="{{ route('eleve.planning', ['week' => $week->copy()->subWeek()->toDateString()]) }}" class="underline">&larr; Semaine précédente</a>
                    <a href="{{ route('eleve.planning', ['week' => $week->copy()->addWeek()->toDateString()]) }}" class="underline">Semaine suivante &rarr;</a>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                            <tr>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Horaire</th>
                                <th class="px-4 py-3">Moniteur</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Présence</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($sessions as $session)
                                <tr>
                                    <td class="px-4 py-3">{{ $session->scheduled_date->format('d/m') }}</td>
                                    <td class="px-4 py-3">{{ substr($session->starts_at, 0, 5) }}–{{ substr($session->ends_at, 0, 5) }}</td>
                                    <td class="px-4 py-3">{{ $session->instructor->name }}</td>
                                    <td class="px-4 py-3">{{ $session->type->label() }}</td>
                                    <td class="px-4 py-3">{{ $session->presence->label() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Aucune séance cette semaine.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
