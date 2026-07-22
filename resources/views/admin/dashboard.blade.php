<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Tableau de bord
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Élèves</div>
                    <div class="text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $studentsByStage->sum() }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Anciens élèves</div>
                    <div class="text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $studentsByStage->get('Ancien élève', 0) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Taux de réussite examens</div>
                    <div class="text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $examStats['rate'] }}%</div>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Alertes flotte</div>
                    <div class="text-3xl font-semibold {{ $fleetAlertCount > 0 ? 'text-amber-600' : 'text-gray-900 dark:text-gray-100' }}">{{ $fleetAlertCount }}</div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">Recettes des 6 derniers mois</div>
                    <a href="{{ route('reports.revenue.csv') }}" class="text-xs text-indigo-600 dark:text-indigo-400 underline">Exporter en CSV</a>
                </div>
                @php $max = max(1, $revenueByMonth->max('total')); @endphp
                <div class="space-y-2">
                    @foreach ($revenueByMonth as $row)
                        <div class="flex items-center gap-3 text-sm">
                            <div class="w-16 text-gray-500">{{ $row['month'] }}</div>
                            <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded h-4 overflow-hidden">
                                <div class="bg-indigo-500 h-4" style="width: {{ $row['total'] > 0 ? max(2, $row['total'] / $max * 100) : 0 }}%"></div>
                            </div>
                            <div class="w-28 text-right text-gray-700 dark:text-gray-300">{{ number_format($row['total'], 0, ',', ' ') }} FCFA</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Examens</div>
                    <div class="grid grid-cols-3 gap-2 text-center text-sm">
                        <div>
                            <div class="text-2xl font-semibold text-emerald-600">{{ $examStats['passed'] }}</div>
                            <div class="text-gray-500">Réussis</div>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold text-red-600">{{ $examStats['failed'] }}</div>
                            <div class="text-gray-500">Échoués</div>
                        </div>
                        <div>
                            <div class="text-2xl font-semibold text-gray-500">{{ $examStats['pending'] }}</div>
                            <div class="text-gray-500">En attente</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Élèves par étape</div>
                    <ul class="text-sm divide-y divide-gray-100 dark:divide-gray-700 max-h-48 overflow-y-auto">
                        @foreach ($studentsByStage as $stage => $count)
                            @if ($count > 0)
                                <li class="py-1.5 flex justify-between">
                                    <span>{{ $stage }}</span>
                                    <span class="font-medium">{{ $count }}</span>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>

            <a href="{{ route('students.index') }}" class="inline-block text-indigo-600 dark:text-indigo-400 underline text-sm">
                Voir la liste des élèves &rarr;
            </a>
        </div>
    </div>
</x-app-layout>
