@php
    $motifs = collect(\App\Domain\Recyclage\Enums\RecyclageMotif::cases())->mapWithKeys(fn ($m) => [$m->value => $m->label()])->all();
    $total = $entries->getCollection()->sum(fn ($entry) => (float) $entry->amount);
@endphp

<x-app-layout :title="'Recyclage'">
    <x-layout.page-header
        title="Recyclage et tests"
        description="Sessions de remise à niveau pour les conducteurs déjà titulaires du permis."
        :breadcrumbs="[['label' => 'Recyclage']]"
    >
        <x-slot:actions>
            @can('create', \App\Domain\Recyclage\Models\RecyclageEntry::class)
                <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-recyclage' })">Nouvelle inscription</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        <x-data.kpi label="Recettes (page)" :value="number_format((float) $total, 0, ',', ' ').' FCFA'" icon="wallet" :hint="$entries->total().' inscription(s)'" />

        <x-ui.card variant="solid" padding="p-0">
            @if ($entries->isEmpty())
                <x-ui.empty-state icon="refresh-cw" title="Aucune inscription" description="Enregistrez les sessions de recyclage et de test pour garder l’historique et les encaissements." class="py-10" />
            @else
                <div class="px-1 pb-1">
                    <x-data.table caption="Inscriptions au recyclage">
                        <thead>
                            <tr>
                                <th scope="col">Participant</th>
                                <th scope="col">Motif</th>
                                <th scope="col">Date</th>
                                <th scope="col">Moniteur</th>
                                <th scope="col" class="text-right">Montant</th>
                                <th scope="col"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entries as $entry)
                                <tr>
                                    <td data-label="Participant">
                                        <span class="block text-sm font-medium text-ink">{{ $entry->full_name }}</span>
                                        <span class="block text-[12px] text-ink-3">{{ $entry->phone ?: '—' }}</span>
                                    </td>
                                    <td data-label="Motif"><x-ui.badge tone="neutral">{{ $entry->motif?->label() }}</x-ui.badge></td>
                                    <td data-label="Date"><x-ui.date :value="$entry->session_date" /></td>
                                    <td data-label="Moniteur">{{ $entry->instructor?->name ?? '—' }}</td>
                                    <td data-label="Montant" class="num"><x-ui.money :amount="$entry->amount" /></td>
                                    <td data-label="">
                                        <div class="flex justify-end">
                                            @can('delete', $entry)
                                                <x-ui.button size="sm" variant="ghost" class="text-danger" icon="trash-2"
                                                    x-on:click="$dispatch('open-confirm', { name: 'delete-recyclage-{{ $entry->id }}' })">Supprimer</x-ui.button>
                                                <x-ui.confirm
                                                    name="delete-recyclage-{{ $entry->id }}"
                                                    title="Supprimer cette inscription ?"
                                                    :consequence="'L’inscription de '.$entry->full_name.' sera retirée de l’historique. Cette action est définitive.'"
                                                    confirm-label="Supprimer"
                                                    :action="route('recyclage.destroy', $entry)"
                                                />
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-data.table>
                </div>
                <x-ui.pagination :paginator="$entries" label="Pagination du recyclage" />
            @endif
        </x-ui.card>
    </div>

    @can('create', \App\Domain\Recyclage\Models\RecyclageEntry::class)
        <x-ui.dialog name="new-recyclage" title="Nouvelle inscription au recyclage">
            <x-form.errors />
            <form method="POST" action="{{ route('recyclage.store') }}" class="space-y-4">
                @csrf
                <x-form.field name="full_name" label="Nom du participant" required>
                    <x-form.input name="full_name" required />
                </x-form.field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="motif" label="Motif" required>
                        <x-form.select name="motif" :options="$motifs" :selected="'recyclage'" />
                    </x-form.field>
                    <x-form.field name="phone" label="Téléphone" optional>
                        <x-form.input name="phone" type="tel" inputmode="tel" icon="phone" />
                    </x-form.field>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-form.field name="session_date" label="Date de session" required>
                        <x-form.input name="session_date" type="date" :value="now()->format('Y-m-d')" required />
                    </x-form.field>
                    <x-form.field name="instructor_id" label="Moniteur" optional>
                        <x-form.select name="instructor_id" :options="$instructors->pluck('name', 'id')->all()" placeholder="Non affecté" />
                    </x-form.field>
                    <x-form.field name="amount" label="Montant (FCFA)" required>
                        <x-form.input name="amount" type="number" min="0" step="1" inputmode="numeric" required />
                    </x-form.field>
                </div>
                <div class="flex justify-end gap-2">
                    <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'new-recyclage' })">Annuler</x-ui.button>
                    <x-ui.button type="submit">Enregistrer</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>
    @endcan
</x-app-layout>
