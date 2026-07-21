<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $vehicle->plate }} — {{ $vehicle->brand }} {{ $vehicle->model }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6 grid grid-cols-3 gap-4 text-sm">
                <div><span class="text-gray-500">Statut</span><br>{{ $vehicle->status->label() }}</div>
                <div><span class="text-gray-500">Kilométrage</span><br>{{ number_format($vehicle->mileage, 0, ',', ' ') }} km</div>
                <div><span class="text-gray-500">Catégorie</span><br>{{ $vehicle->category }}</div>
                <div><span class="text-gray-500">Contrôle technique</span><br>{{ optional($vehicle->technical_inspection_expires_at)->format('d/m/Y') ?? '—' }}</div>
                <div><span class="text-gray-500">Assurance</span><br>{{ optional($vehicle->insurance_expires_at)->format('d/m/Y') ?? '—' }}</div>
            </div>

            @can('update', $vehicle)
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Enregistrer un entretien</div>
                    <form method="POST" action="{{ route('fleet.maintenance.store', $vehicle) }}" class="grid grid-cols-4 gap-3 items-end">
                        @csrf
                        <x-text-input name="type" placeholder="Vidange, pneus…" class="block w-full" required />
                        <x-text-input type="number" step="0.01" name="cost" placeholder="Coût" class="block w-full" />
                        <x-text-input type="number" name="mileage" placeholder="Km" class="block w-full" />
                        <x-text-input type="date" name="performed_on" class="block w-full" required />
                        <div class="col-span-4">
                            <x-primary-button>Enregistrer</x-primary-button>
                        </div>
                    </form>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Enregistrer un plein</div>
                    <form method="POST" action="{{ route('fleet.fuel.store', $vehicle) }}" class="grid grid-cols-4 gap-3 items-end">
                        @csrf
                        <x-text-input type="number" step="0.01" name="liters" placeholder="Litres" class="block w-full" required />
                        <x-text-input type="number" step="0.01" name="cost" placeholder="Coût" class="block w-full" required />
                        <x-text-input type="number" name="mileage" placeholder="Km" class="block w-full" />
                        <x-text-input type="date" name="filled_on" class="block w-full" required />
                        <div class="col-span-4">
                            <x-primary-button>Enregistrer</x-primary-button>
                        </div>
                    </form>
                </div>
            @endcan

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Historique entretiens</div>
                <ul class="text-sm divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($vehicle->maintenanceLogs as $log)
                        <li class="py-2 flex justify-between">
                            <span>{{ $log->performed_on->format('d/m/Y') }} — {{ $log->type }}</span>
                            <span>{{ number_format($log->cost, 0, ',', ' ') }} FCFA</span>
                        </li>
                    @empty
                        <li class="py-2 text-gray-500">Aucun entretien.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
