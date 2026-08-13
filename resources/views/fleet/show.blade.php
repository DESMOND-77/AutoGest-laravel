<x-app-layout>
    <x-slot name="header">{{ $vehicle->plate }} — {{ $vehicle->brand }} {{ $vehicle->model }}</x-slot>

    <div class="py-6 space-y-5 max-w-3xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-card>
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-lg font-semibold text-content">{{ $vehicle->plate }}</h1>
                <x-badge :variant="$vehicle->status->value === 'active' ? 'success' : ($vehicle->status->value === 'maintenance' ? 'warning' : 'danger')">
                    {{ $vehicle->status->label() }}
                </x-badge>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                <div><span class="text-content-muted">Kilométrage</span><p class="text-content font-medium mt-0.5">{{ number_format($vehicle->mileage, 0, ',', ' ') }} km</p></div>
                <div><span class="text-content-muted">Catégorie</span><p class="text-content font-medium mt-0.5">{{ $vehicle->category }}</p></div>
                <div><span class="text-content-muted">Contrôle technique</span><p class="text-content font-medium mt-0.5">{{ optional($vehicle->technical_inspection_expires_at)->format('d/m/Y') ?? '—' }}</p></div>
                <div><span class="text-content-muted">Assurance</span><p class="text-content font-medium mt-0.5">{{ optional($vehicle->insurance_expires_at)->format('d/m/Y') ?? '—' }}</p></div>
            </div>
        </x-card>

        @can('update', $vehicle)
            <x-card>
                <div class="text-sm font-semibold text-content mb-3">Enregistrer un entretien</div>
                <form method="POST" action="{{ route('fleet.maintenance.store', $vehicle) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                    @csrf
                    <x-text-input name="type" placeholder="Vidange, pneus…" class="block w-full" required />
                    <x-text-input type="number" step="0.01" name="cost" placeholder="Coût" class="block w-full" />
                    <x-text-input type="number" name="mileage" placeholder="Km" class="block w-full" />
                    <x-text-input type="date" name="performed_on" class="block w-full" required />
                    <div class="sm:col-span-4">
                        <x-primary-button>Enregistrer</x-primary-button>
                    </div>
                </form>
            </x-card>

            <x-card>
                <div class="text-sm font-semibold text-content mb-3">Enregistrer un plein</div>
                <form method="POST" action="{{ route('fleet.fuel.store', $vehicle) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                    @csrf
                    <x-text-input type="number" step="0.01" name="liters" placeholder="Litres" class="block w-full" required />
                    <x-text-input type="number" step="0.01" name="cost" placeholder="Coût" class="block w-full" required />
                    <x-text-input type="number" name="mileage" placeholder="Km" class="block w-full" />
                    <x-text-input type="date" name="filled_on" class="block w-full" required />
                    <div class="sm:col-span-4">
                        <x-primary-button>Enregistrer</x-primary-button>
                    </div>
                </form>
            </x-card>
        @endcan

        <x-card>
            <div class="text-sm font-semibold text-content mb-2">Historique entretiens</div>
            <ul class="text-sm divide-y divide-border/60">
                @forelse ($vehicle->maintenanceLogs as $log)
                    <li class="py-2.5 flex justify-between">
                        <span class="text-content">{{ $log->performed_on->format('d/m/Y') }} — {{ $log->type }}</span>
                        <span class="text-content-secondary">{{ number_format($log->cost, 0, ',', ' ') }} FCFA</span>
                    </li>
                @empty
                    <li class="py-2.5 text-content-muted">Aucun entretien.</li>
                @endforelse
            </ul>
        </x-card>

        <x-card>
            <div class="text-sm font-semibold text-content mb-3">Documents</div>

            @php
                $vehicleDocuments = \App\Domain\Documents\Models\Document::query()
                    ->where('documentable_type', $vehicle->getMorphClass())
                    ->where('documentable_id', $vehicle->id)
                    ->where('is_current', true)
                    ->latest()
                    ->get();
            @endphp

            <ul class="text-sm divide-y divide-border/60 mb-4">
                @forelse ($vehicleDocuments as $document)
                    <li class="py-2.5 flex justify-between items-center">
                        <span class="text-content">{{ $document->type->label() }} — {{ $document->original_name }} (v{{ $document->version }})</span>
                        <a href="{{ route('documents.download', $document) }}" class="text-xs text-primary hover:underline">Télécharger</a>
                    </li>
                @empty
                    <li class="py-2.5 text-content-muted">Aucun document.</li>
                @endforelse
            </ul>

            @can('update', $vehicle)
                <form method="POST" action="{{ route('fleet.documents.store', $vehicle) }}" enctype="multipart/form-data" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end pt-2">
                    @csrf
                    <select name="type" class="rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm block w-full">
                        @foreach (\App\Domain\Documents\Enums\DocumentType::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </select>
                    <input type="file" name="file" class="text-sm text-content" required>
                    <x-primary-button>Déposer</x-primary-button>
                </form>
            @endcan
        </x-card>
    </div>
</x-app-layout>
