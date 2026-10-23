@php
    $maintenanceTotal = $vehicle->maintenanceLogs->sum(fn ($log) => (float) $log->cost);
    $fuelTotal = $vehicle->fuelLogs->sum(fn ($log) => (float) $log->cost);
    $fuelLiters = $vehicle->fuelLogs->sum(fn ($log) => (float) $log->liters);
@endphp

<x-app-layout :title="$vehicle->plate">
    <x-layout.page-header
        :title="$vehicle->plate"
        :description="trim($vehicle->brand.' '.$vehicle->model).($vehicle->year ? ' · '.$vehicle->year : '').($vehicle->category ? ' · permis '.$vehicle->category : '')"
        :breadcrumbs="[['label' => 'Flotte', 'href' => route('fleet.index')], ['label' => $vehicle->plate]]"
    >
        <x-slot:actions>
            <x-ui.status :value="$vehicle->status" />
            @can('create', \App\Domain\Fleet\Models\Vehicle::class)
                <x-ui.button variant="secondary" icon="wrench" x-on:click="$dispatch('open-dialog', { name: 'new-maintenance' })">Entretien</x-ui.button>
                <x-ui.button variant="secondary" icon="fuel" x-on:click="$dispatch('open-dialog', { name: 'new-fuel' })">Plein</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-ui.card title="Entretiens" :description="$vehicle->maintenanceLogs->count().' intervention(s) · '.number_format($maintenanceTotal, 0, ',', ' ').' FCFA'" variant="solid" padding="p-0">
                @if ($vehicle->maintenanceLogs->isEmpty())
                    <x-ui.empty-state icon="wrench" title="Aucun entretien" description="Enregistrez les vidanges, pneus et réparations pour garder l’historique du véhicule." class="py-8" />
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($vehicle->maintenanceLogs as $log)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 max-sm:px-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-ink">{{ $log->type }}</p>
                                    <p class="text-[12.5px] text-ink-3">
                                        <x-ui.date :value="$log->performed_on" />
                                        {{ $log->mileage ? ' · '.number_format((int) $log->mileage, 0, ',', ' ').' km' : '' }}
                                        {{ $log->next_due_on ? ' · prochain : '.$log->next_due_on->format('d/m/Y') : '' }}
                                    </p>
                                    @if ($log->description)
                                        <p class="mt-0.5 text-[12.5px] text-ink-2">{{ $log->description }}</p>
                                    @endif
                                </div>
                                <x-ui.money :amount="$log->cost" class="text-sm font-semibold text-ink" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            <x-ui.card title="Pleins de carburant" :description="$fuelLiters.' L · '.number_format($fuelTotal, 0, ',', ' ').' FCFA'" variant="solid" padding="p-0">
                @if ($vehicle->fuelLogs->isEmpty())
                    <x-ui.empty-state icon="fuel" title="Aucun plein" description="Suivez la consommation réelle pour détecter les anomalies." class="py-8" />
                @else
                    <div class="px-1 pb-1">
                        <x-data.table caption="Pleins de carburant">
                            <thead>
                                <tr>
                                    <th scope="col">Date</th>
                                    <th scope="col" class="text-right">Litres</th>
                                    <th scope="col" class="text-right">Km</th>
                                    <th scope="col" class="text-right">Coût</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($vehicle->fuelLogs as $log)
                                    <tr>
                                        <td data-label="Date"><x-ui.date :value="$log->filled_on" /></td>
                                        <td data-label="Litres" class="num">{{ number_format((float) $log->liters, 1, ',', ' ') }}</td>
                                        <td data-label="Km" class="num">{{ $log->mileage ? number_format((int) $log->mileage, 0, ',', ' ') : '—' }}</td>
                                        <td data-label="Coût" class="num"><x-ui.money :amount="$log->cost" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-data.table>
                    </div>
                @endif
            </x-ui.card>
        </div>

        <aside class="space-y-4">
            <x-ui.card title="État du véhicule" variant="panel">
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[13px] text-ink-2">Kilométrage</span>
                        <span class="text-sm font-semibold tabular-nums text-ink">{{ number_format((int) $vehicle->mileage, 0, ',', ' ') }} km</span>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[13px] text-ink-2">Couleur</span>
                        <span class="text-sm font-medium text-ink">{{ $vehicle->color ?: '—' }}</span>
                    </div>
                    <div class="space-y-1.5 border-t border-line pt-3">
                        <x-domain.expiry-chip :date="$vehicle->technical_inspection_expires_at" label="Contrôle technique" />
                        <x-domain.expiry-chip :date="$vehicle->insurance_expires_at" label="Assurance" icon="shield" />
                    </div>
                </div>
            </x-ui.card>

            @can('create', \App\Domain\Documents\Models\Document::class)
                <x-ui.card title="Documents" variant="solid">
                    <p class="text-[13px] text-ink-2">Carte grise, attestation d’assurance, contrôle technique.</p>
                    <form method="POST" action="{{ route('fleet.documents.store', $vehicle) }}" enctype="multipart/form-data" class="mt-3 space-y-2">
                        @csrf
                        <x-form.select name="type" aria-label="Type de document"
                            :options="collect(\App\Domain\Documents\Enums\DocumentType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all()" />
                        <x-form.file-drop name="file" label="Déposer un document" />
                        <x-ui.button type="submit" size="sm" variant="secondary" icon="upload">Téléverser</x-ui.button>
                    </form>
                </x-ui.card>
            @endcan

            @can('create', \App\Domain\Fleet\Models\Vehicle::class)
                <x-ui.card title="Suppression" variant="solid">
                    <p class="text-[13px] text-ink-2">Un véhicule déjà utilisé dans des séances ne doit pas être supprimé : passez-le « hors service ».</p>
                    <x-ui.button variant="ghost" class="mt-2 text-danger" icon="trash-2"
                        x-on:click="$dispatch('open-confirm', { name: 'delete-vehicle-{{ $vehicle->id }}' })">Supprimer le véhicule</x-ui.button>
                    <x-ui.confirm
                        name="delete-vehicle-{{ $vehicle->id }}"
                        title="Supprimer ce véhicule ?"
                        :consequence="'Le véhicule '.$vehicle->plate.' sera retiré de la flotte. L’historique des séances reste intact.'"
                        confirm-label="Supprimer"
                        :action="route('fleet.destroy', $vehicle)"
                    />
                </x-ui.card>
            @endcan
        </aside>
    </div>

    @can('create', \App\Domain\Fleet\Models\Vehicle::class)
        <x-ui.dialog name="new-maintenance" title="Enregistrer un entretien">
            <form method="POST" action="{{ route('fleet.maintenance.store', $vehicle) }}" class="space-y-4">
                @csrf
                <x-form.field name="type" label="Type d’intervention" required>
                    <x-form.input name="type" placeholder="Vidange, pneus, freins…" required />
                </x-form.field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="performed_on" label="Date" required>
                        <x-form.input name="performed_on" type="date" :value="now()->format('Y-m-d')" required />
                    </x-form.field>
                    <x-form.field name="next_due_on" label="Prochaine échéance" optional>
                        <x-form.input name="next_due_on" type="date" />
                    </x-form.field>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="cost" label="Coût (FCFA)" optional>
                        <x-form.input name="cost" type="number" min="0" step="1" />
                    </x-form.field>
                    <x-form.field name="mileage" label="Kilométrage" optional>
                        <x-form.input name="mileage" type="number" min="0" :value="$vehicle->mileage" suffix="km" />
                    </x-form.field>
                </div>
                <x-form.field name="description" label="Détails" optional>
                    <x-form.textarea name="description" />
                </x-form.field>
                <div class="flex justify-end gap-2">
                    <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'new-maintenance' })">Annuler</x-ui.button>
                    <x-ui.button type="submit">Enregistrer</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>

        <x-ui.dialog name="new-fuel" title="Enregistrer un plein">
            <form method="POST" action="{{ route('fleet.fuel.store', $vehicle) }}" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="liters" label="Litres" required>
                        <x-form.input name="liters" type="number" min="0.1" step="0.01" required suffix="L" />
                    </x-form.field>
                    <x-form.field name="cost" label="Coût (FCFA)" required>
                        <x-form.input name="cost" type="number" min="0" step="1" required />
                    </x-form.field>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="mileage" label="Kilométrage" optional>
                        <x-form.input name="mileage" type="number" min="0" :value="$vehicle->mileage" suffix="km" />
                    </x-form.field>
                    <x-form.field name="filled_on" label="Date" required>
                        <x-form.input name="filled_on" type="date" :value="now()->format('Y-m-d')" required />
                    </x-form.field>
                </div>
                <div class="flex justify-end gap-2">
                    <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'new-fuel' })">Annuler</x-ui.button>
                    <x-ui.button type="submit">Enregistrer le plein</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>
    @endcan
</x-app-layout>
