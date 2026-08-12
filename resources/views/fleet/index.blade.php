<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Flotte
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">{{ session('status') }}</div>
            @endif

            @can('create', \App\Domain\Fleet\Models\Vehicle::class)
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Nouveau véhicule</div>
                    <form id="fleet-create-form" method="POST" action="{{ route('fleet.store') }}" class="grid grid-cols-4 gap-3 items-end">
                        @csrf
                        <div>
                            <x-input-label for="plate" value="Immatriculation" />
                            <x-text-input id="plate" name="plate" class="block mt-1 w-full" required />
                        </div>
                        <div>
                            <x-input-label for="brand" value="Marque" />
                            <x-text-input id="brand" name="brand" class="block mt-1 w-full" />
                        </div>
                        <div>
                            <x-input-label for="category" value="Catégorie" />
                            <x-text-input id="category" name="category" class="block mt-1 w-full" value="B" required />
                        </div>
                        <div>
                            <x-primary-button>Ajouter</x-primary-button>
                        </div>
                        <div>
                            <x-input-label for="technical_inspection_expires_at" value="Fin contrôle technique" />
                            <x-text-input id="technical_inspection_expires_at" type="date" name="technical_inspection_expires_at" class="block mt-1 w-full" />
                        </div>
                        <div>
                            <x-input-label for="insurance_expires_at" value="Fin assurance" />
                            <x-text-input id="insurance_expires_at" type="date" name="insurance_expires_at" class="block mt-1 w-full" />
                        </div>
                    </form>
                </div>
            @endcan

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Plaque</th>
                            <th class="px-4 py-3">Marque / modèle</th>
                            <th class="px-4 py-3">Statut</th>
                            <th class="px-4 py-3">Km</th>
                            <th class="px-4 py-3">Alerte</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($vehicles as $vehicle)
                            <tr>
                                <td class="px-4 py-3">
                                    <a href="{{ route('fleet.show', $vehicle) }}" class="font-medium text-indigo-600 dark:text-indigo-400">
                                        {{ $vehicle->plate }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">{{ $vehicle->brand }} {{ $vehicle->model }}</td>
                                <td class="px-4 py-3">{{ $vehicle->status->label() }}</td>
                                <td class="px-4 py-3">{{ number_format($vehicle->mileage, 0, ',', ' ') }}</td>
                                <td class="px-4 py-3">
                                    @if ($expiringSoon->contains($vehicle->id))
                                        <span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-800">CT/assurance sous 30j</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-empty-table-row
                                colspan="5"
                                title="Aucun véhicule enregistré."
                                message="Ajoutez votre premier véhicule pour commencer à planifier des séances de conduite."
                                :action="Auth::user()->can('create', \App\Domain\Fleet\Models\Vehicle::class) ? '#fleet-create-form' : null"
                                action-label="Ajouter un véhicule"
                            />
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
