@php
    $licenses = collect(\App\Domain\Students\Enums\LicenseCategory::cases())->mapWithKeys(fn ($c) => [$c->value => 'Permis '.$c->value])->all();
@endphp

<x-app-layout :title="'Forfaits'">
    <x-layout.page-header
        title="Forfaits de formation"
        description="Les forfaits pré-remplissent les factures : nom, volume horaire et tarif."
        :breadcrumbs="[['label' => 'Finance', 'href' => route('finance.invoices.index')], ['label' => 'Forfaits']]"
    >
        <x-slot:actions>
            @can('create', \App\Domain\Finance\Models\TrainingPackage::class)
                <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-package' })">Nouveau forfait</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    @if ($packages->isEmpty())
        <x-ui.card variant="solid">
            <x-ui.empty-state icon="package" title="Aucun forfait" description="Créez vos formules (code, conduite, accélérée…) pour facturer en deux clics.">
                <x-slot:actions>
                    @can('create', \App\Domain\Finance\Models\TrainingPackage::class)
                        <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-package' })">Nouveau forfait</x-ui.button>
                    @endcan
                </x-slot:actions>
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($packages as $package)
                <article class="material-panel flex flex-col p-5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h2 class="truncate text-section-title text-ink">{{ $package->name }}</h2>
                            <p class="mt-0.5 text-[12.5px] text-ink-3">
                                Permis {{ $package->license_category }}{{ $package->hours ? ' · '.$package->hours.' h' : '' }}
                            </p>
                        </div>
                        <x-ui.badge :tone="$package->active ? 'success' : 'neutral'">{{ $package->active ? 'Actif' : 'Inactif' }}</x-ui.badge>
                    </div>

                    @if ($package->description)
                        <p class="mt-2 line-clamp-2 text-[13px] text-ink-2">{{ $package->description }}</p>
                    @endif

                    <p class="mt-3 text-2xl font-bold text-ink"><x-ui.money :amount="$package->price" /></p>

                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-line pt-3">
                        @can('update', $package)
                            <x-ui.button size="sm" variant="secondary" icon="pencil"
                                x-on:click="$dispatch('open-dialog', { name: 'edit-package-{{ $package->id }}' })">Modifier</x-ui.button>
                        @endcan
                        @can('delete', $package)
                            <x-ui.button size="sm" variant="ghost" class="text-danger" icon="trash-2"
                                x-on:click="$dispatch('open-confirm', { name: 'delete-package-{{ $package->id }}' })">Supprimer</x-ui.button>
                            <x-ui.confirm
                                name="delete-package-{{ $package->id }}"
                                title="Supprimer ce forfait ?"
                                :consequence="'« '.$package->name.' » ne sera plus proposé lors de la création d’une facture. Les factures déjà émises ne changent pas.'"
                                confirm-label="Supprimer"
                                :action="route('finance.packages.destroy', $package)"
                            />
                        @endcan
                    </div>
                </article>

                @can('update', $package)
                    <x-ui.dialog name="edit-package-{{ $package->id }}" title="Modifier le forfait">
                        <form method="POST" action="{{ route('finance.packages.update', $package) }}" class="space-y-4">
                            @csrf
                            @method('PATCH')
                            <x-form.field name="name" label="Nom" required>
                                <x-form.input name="name" :value="$package->name" required />
                            </x-form.field>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <x-form.field name="license_category" label="Catégorie de permis" required>
                                    <x-form.select name="license_category" :options="$licenses" :selected="$package->license_category" />
                                </x-form.field>
                                <x-form.field name="hours" label="Volume horaire" optional>
                                    <x-form.input name="hours" type="number" min="1" :value="$package->hours" suffix="h" />
                                </x-form.field>
                            </div>
                            <x-form.field name="price" label="Tarif (FCFA)" required>
                                <x-form.input name="price" type="number" min="0" step="1" :value="(int) $package->price" required />
                            </x-form.field>
                            <x-form.field name="description" label="Description" optional>
                                <x-form.textarea name="description" :value="$package->description" />
                            </x-form.field>
                            <div class="flex justify-end gap-2">
                                <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'edit-package-{{ $package->id }}' })">Annuler</x-ui.button>
                                <x-ui.button type="submit">Enregistrer</x-ui.button>
                            </div>
                        </form>
                    </x-ui.dialog>
                @endcan
            @endforeach
        </div>
    @endif

    @can('create', \App\Domain\Finance\Models\TrainingPackage::class)
        <x-ui.dialog name="new-package" title="Nouveau forfait" description="Le forfait sera actif immédiatement." :open="$errors->any()">
            <x-form.errors />
            <form method="POST" action="{{ route('finance.packages.store') }}" class="space-y-4">
                @csrf
                <x-form.field name="name" label="Nom du forfait" required>
                    <x-form.input name="name" placeholder="Permis B — formule accélérée" required />
                </x-form.field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="license_category" label="Catégorie de permis" required>
                        <x-form.select name="license_category" :options="$licenses" :selected="old('license_category', 'B')" />
                    </x-form.field>
                    <x-form.field name="hours" label="Volume horaire" optional>
                        <x-form.input name="hours" type="number" min="1" suffix="h" />
                    </x-form.field>
                </div>
                <x-form.field name="price" label="Tarif (FCFA)" required>
                    <x-form.input name="price" type="number" min="0" step="1" inputmode="numeric" required />
                </x-form.field>
                <x-form.field name="description" label="Description" optional>
                    <x-form.textarea name="description" placeholder="Ce qui est inclus : cours de code, 20 h de conduite…" />
                </x-form.field>
                <div class="flex justify-end gap-2">
                    <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'new-package' })">Annuler</x-ui.button>
                    <x-ui.button type="submit">Créer le forfait</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>
    @endcan
</x-app-layout>
