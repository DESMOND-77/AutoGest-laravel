@php
    $statuses = collect(\App\Domain\Fleet\Enums\VehicleStatus::cases());
    $counts = $statuses->mapWithKeys(fn ($s) => [$s->value => $vehicles->where('status', $s)->count()]);
    $alertCount = $vehicles->whereIn('id', $expiringSoon)->count();
    $licenses = collect(\App\Domain\Students\Enums\LicenseCategory::cases())->mapWithKeys(fn ($c) => [$c->value => $c->value])->all();
@endphp

<x-app-layout :title="'Flotte'">
    <x-layout.page-header
        title="Flotte"
        description="Véhicules, échéances de contrôle technique et d’assurance, kilométrage."
        :breadcrumbs="[['label' => 'Flotte']]"
    >
        <x-slot:actions>
            @can('create', \App\Domain\Fleet\Models\Vehicle::class)
                <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-vehicle' })">Ajouter un véhicule</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        @if ($alertCount > 0)
            <x-ui.alert tone="warning" title="{{ $alertCount }} véhicule(s) avec une échéance proche">
                Contrôle technique ou assurance dans les 30 prochains jours. Ouvrez la fiche du véhicule pour mettre les dates à jour après le passage au centre.
            </x-ui.alert>
        @endif

        <div class="grid gap-3 sm:grid-cols-3">
            <x-data.kpi label="En service" :value="$counts->get('active', 0)" icon="car" href="{{ route('fleet.index') }}" />
            <x-data.kpi label="En entretien" :value="$counts->get('maintenance', 0)" icon="wrench" />
            <x-data.kpi label="Hors service" :value="$counts->get('out_of_service', 0)" icon="ban" />
        </div>

        @if ($vehicles->isEmpty())
            <x-ui.card variant="solid">
                <x-ui.empty-state icon="car" title="Aucun véhicule" description="Ajoutez vos véhicules pour planifier des séances de conduite et suivre les échéances réglementaires.">
                    <x-slot:actions>
                        @can('create', \App\Domain\Fleet\Models\Vehicle::class)
                            <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-vehicle' })">Ajouter un véhicule</x-ui.button>
                        @endcan
                    </x-slot:actions>
                </x-ui.empty-state>
            </x-ui.card>
        @else
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($vehicles as $vehicle)
                    <x-domain.vehicle-card :vehicle="$vehicle" :expiring="in_array($vehicle->id, $expiringSoon->all(), true)" />
                @endforeach
            </div>

            <x-ui.card title="Échéances" variant="solid" padding="p-0">
                <div class="px-1 pb-1">
                    <x-data.table caption="Échéances des véhicules">
                        <thead>
                            <tr>
                                <th scope="col">Véhicule</th>
                                <th scope="col">Statut</th>
                                <th scope="col">Contrôle technique</th>
                                <th scope="col">Assurance</th>
                                <th scope="col" class="text-right">Km</th>
                                <th scope="col"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($vehicles as $vehicle)
                                <tr>
                                    <td data-label="Véhicule">
                                        <a href="{{ route('fleet.show', $vehicle) }}" class="text-sm font-medium text-ink hover:text-accent">{{ $vehicle->plate }}</a>
                                        <span class="block text-[12px] text-ink-3">{{ $vehicle->brand }} {{ $vehicle->model }}</span>
                                    </td>
                                    <td data-label="Statut"><x-ui.status :value="$vehicle->status" /></td>
                                    <td data-label="Contrôle technique"><x-domain.expiry-chip :date="$vehicle->technical_inspection_expires_at" label="CT" /></td>
                                    <td data-label="Assurance"><x-domain.expiry-chip :date="$vehicle->insurance_expires_at" label="Assurance" icon="shield" /></td>
                                    <td data-label="Km" class="num">{{ number_format((int) $vehicle->mileage, 0, ',', ' ') }}</td>
                                    <td data-label="">
                                        <div class="flex justify-end">
                                            <x-ui.button :href="route('fleet.show', $vehicle)" size="sm" variant="ghost" icon-right="chevron-right">Fiche</x-ui.button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-data.table>
                </div>
            </x-ui.card>
        @endif
    </div>

    @can('create', \App\Domain\Fleet\Models\Vehicle::class)
        <x-ui.dialog name="new-vehicle" title="Ajouter un véhicule" :open="$errors->any()">
            <x-form.errors />
            <form method="POST" action="{{ route('fleet.store') }}" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="plate" label="Immatriculation" required>
                        <x-form.input name="plate" placeholder="LB-123-AB" required />
                    </x-form.field>
                    <x-form.field name="category" label="Catégorie de permis" required>
                        <x-form.select name="category" :options="$licenses" :selected="old('category', 'B')" />
                    </x-form.field>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-form.field name="brand" label="Marque" optional>
                        <x-form.input name="brand" placeholder="Toyota" />
                    </x-form.field>
                    <x-form.field name="model" label="Modèle" optional>
                        <x-form.input name="model" placeholder="Yaris" />
                    </x-form.field>
                    <x-form.field name="year" label="Année" optional>
                        <x-form.input name="year" type="number" min="1990" :max="date('Y') + 1" />
                    </x-form.field>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="technical_inspection_expires_at" label="Contrôle technique" optional hint="Date d’expiration.">
                        <x-form.input name="technical_inspection_expires_at" type="date" />
                    </x-form.field>
                    <x-form.field name="insurance_expires_at" label="Assurance" optional hint="Date d’expiration.">
                        <x-form.input name="insurance_expires_at" type="date" />
                    </x-form.field>
                </div>
                <div class="flex justify-end gap-2">
                    <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'new-vehicle' })">Annuler</x-ui.button>
                    <x-ui.button type="submit" icon="car">Ajouter le véhicule</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>
    @endcan
</x-app-layout>
