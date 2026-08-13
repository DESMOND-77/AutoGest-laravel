<x-app-layout>
    <x-slot name="header">Flotte</x-slot>

    <div class="py-6 space-y-5 max-w-6xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        @php
            $statusCounts = $vehicles->countBy(fn ($v) => $v->status->value);
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-kpi-card icon="truck" label="En service" :value="$statusCounts->get('active', 0)" />
            <x-kpi-card icon="cog" label="En entretien" :value="$statusCounts->get('maintenance', 0)" />
            <x-kpi-card icon="exclamation-triangle" label="Hors service" :value="$statusCounts->get('out_of_service', 0)" />
        </div>

        @can('create', \App\Domain\Fleet\Models\Vehicle::class)
            <x-card>
                <div class="text-sm font-semibold text-content mb-3">Nouveau véhicule</div>
                <form id="fleet-create-form" method="POST" action="{{ route('fleet.store') }}" class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-3 items-end">
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
                        <x-input-label for="technical_inspection_expires_at" value="Fin contrôle technique" />
                        <x-text-input id="technical_inspection_expires_at" type="date" name="technical_inspection_expires_at" class="block mt-1 w-full" />
                    </div>
                    <div>
                        <x-input-label for="insurance_expires_at" value="Fin assurance" />
                        <x-text-input id="insurance_expires_at" type="date" name="insurance_expires_at" class="block mt-1 w-full" />
                    </div>
                    <div class="lg:col-span-5">
                        <x-primary-button>Ajouter</x-primary-button>
                    </div>
                </form>
            </x-card>
        @endcan

        @if ($vehicles->isEmpty())
            <x-card>
                <div class="text-center py-8">
                    <p class="text-sm font-medium text-content">Aucun véhicule enregistré.</p>
                    <p class="text-sm text-content-muted mt-1">Ajoutez votre premier véhicule pour commencer à planifier des séances de conduite.</p>
                </div>
            </x-card>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($vehicles as $vehicle)
                    <a href="{{ route('fleet.show', $vehicle) }}" class="block bg-surface rounded-ui-lg shadow-soft p-5 hover:shadow-soft-hover transition">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-semibold text-content">{{ $vehicle->plate }}</p>
                                <p class="text-sm text-content-secondary truncate">{{ $vehicle->brand }} {{ $vehicle->model }}</p>
                            </div>
                            <span class="shrink-0 flex h-10 w-10 items-center justify-center rounded-ui-md bg-primary/10 text-primary">
                                <x-icon name="truck" class="w-5 h-5" />
                            </span>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <x-badge :variant="$vehicle->status->value === 'active' ? 'success' : ($vehicle->status->value === 'maintenance' ? 'warning' : 'danger')">
                                {{ $vehicle->status->label() }}
                            </x-badge>
                            <span class="text-xs text-content-muted">{{ number_format($vehicle->mileage, 0, ',', ' ') }} km</span>
                        </div>

                        @if ($expiringSoon->contains($vehicle->id))
                            <div class="mt-3 pt-3 border-t border-border/60">
                                <x-badge variant="warning">⚠ CT/assurance sous 30j</x-badge>
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
