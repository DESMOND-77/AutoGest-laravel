@php
    $critical = $products->filter(fn ($product) => $product->reorder_threshold && $product->stock_quantity < $product->reorder_threshold);
@endphp

<x-app-layout :title="'Produits'">
    <x-layout.page-header
        title="Produits"
        description="Catalogue de la boutique : prix de vente, coût d’achat, stock et seuil d’alerte."
        :breadcrumbs="[['label' => 'Boutique', 'href' => route('store.index')], ['label' => 'Produits']]"
    >
        <x-slot:actions>
            @can('create', \App\Domain\Store\Models\Product::class)
                <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-product' })">Nouveau produit</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        @if ($critical->isNotEmpty())
            <x-ui.alert tone="warning" title="{{ $critical->count() }} produit(s) sous le seuil">
                Pensez à réapprovisionner : {{ $critical->pluck('name')->take(4)->join(', ') }}{{ $critical->count() > 4 ? '…' : '' }}.
            </x-ui.alert>
        @endif

        <x-ui.card variant="solid" padding="p-0">
            @if ($products->isEmpty())
                <x-ui.empty-state icon="package" title="Aucun produit" description="Ajoutez vos codes, livres et accessoires pour les vendre au comptoir." class="py-10">
                    <x-slot:actions>
                        @can('create', \App\Domain\Store\Models\Product::class)
                            <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-product' })">Nouveau produit</x-ui.button>
                        @endcan
                    </x-slot:actions>
                </x-ui.empty-state>
            @else
                <div class="px-1 pb-1">
                    <x-data.table caption="Catalogue des produits">
                        <thead>
                            <tr>
                                <th scope="col">Produit</th>
                                <th scope="col">Catégorie</th>
                                <th scope="col" class="text-right">Prix</th>
                                <th scope="col" class="text-right">Coût</th>
                                <th scope="col" class="text-right">Stock</th>
                                <th scope="col">Statut</th>
                                <th scope="col"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $product)
                                <tr>
                                    <td data-label="Produit">
                                        <span class="block text-sm font-medium text-ink">{{ $product->name }}</span>
                                        <span class="block text-[12px] text-ink-3">{{ $product->sku ?: 'Sans référence' }}</span>
                                    </td>
                                    <td data-label="Catégorie">{{ $product->category ?: '—' }}</td>
                                    <td data-label="Prix" class="num"><x-ui.money :amount="$product->price" /></td>
                                    <td data-label="Coût" class="num"><x-ui.money :amount="$product->cost_price" /></td>
                                    <td data-label="Stock" class="num">
                                        <span class="{{ $product->reorder_threshold && $product->stock_quantity < $product->reorder_threshold ? 'font-semibold text-warning' : '' }}">
                                            {{ $product->stock_quantity }}
                                        </span>
                                    </td>
                                    <td data-label="Statut"><x-ui.badge :tone="$product->active ? 'success' : 'neutral'">{{ $product->active ? 'En vente' : 'Retiré' }}</x-ui.badge></td>
                                    <td data-label="">
                                        <div class="flex justify-end">
                                            @can('delete', $product)
                                                <x-ui.button size="sm" variant="ghost" class="text-danger" icon="trash-2"
                                                    x-on:click="$dispatch('open-confirm', { name: 'delete-product-{{ $product->id }}' })">Supprimer</x-ui.button>
                                                <x-ui.confirm
                                                    name="delete-product-{{ $product->id }}"
                                                    title="Supprimer ce produit ?"
                                                    :consequence="'« '.$product->name.' » disparaîtra du catalogue. Les ventes déjà enregistrées ne changent pas.'"
                                                    confirm-label="Supprimer"
                                                    :action="route('store.products.destroy', $product)"
                                                />
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-data.table>
                </div>
            @endif
        </x-ui.card>

        <x-ui.card title="Fournisseurs" description="Les fournisseurs servent aux bons de commande." variant="solid">
            @if ($suppliers->isEmpty())
                <p class="text-[13px] text-ink-3">Aucun fournisseur enregistré.</p>
            @else
                <ul class="flex flex-wrap gap-2">
                    @foreach ($suppliers as $supplier)
                        <li class="rounded-pill border border-line px-3 py-1 text-[12.5px] text-ink-2">
                            {{ $supplier->name }}{{ $supplier->phone ? ' · '.$supplier->phone : '' }}
                        </li>
                    @endforeach
                </ul>
            @endif

            @can('create', \App\Domain\Store\Models\Supplier::class)
                <form method="POST" action="{{ route('store.suppliers.store') }}" class="mt-3 grid gap-2 sm:grid-cols-4 sm:items-end">
                    @csrf
                    <x-form.field name="name" label="Nom du fournisseur" required>
                        <x-form.input name="name" required />
                    </x-form.field>
                    <x-form.field name="phone" label="Téléphone" optional>
                        <x-form.input name="phone" type="tel" inputmode="tel" />
                    </x-form.field>
                    <x-form.field name="email" label="E-mail" optional>
                        <x-form.input name="email" type="email" />
                    </x-form.field>
                    <x-ui.button type="submit" variant="secondary" icon="plus">Ajouter</x-ui.button>
                </form>
            @endcan
        </x-ui.card>
    </div>

    @can('create', \App\Domain\Store\Models\Product::class)
        <x-ui.dialog name="new-product" title="Nouveau produit">
            <x-form.errors />
            <form method="POST" action="{{ route('store.products.store') }}" class="space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="name" label="Nom du produit" required>
                        <x-form.input name="name" placeholder="Code de la route — édition 2026" required />
                    </x-form.field>
                    <x-form.field name="category" label="Catégorie" optional>
                        <x-form.input name="category" placeholder="Livres, accessoires…" />
                    </x-form.field>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="sku" label="Référence" optional>
                        <x-form.input name="sku" />
                    </x-form.field>
                    <x-form.field name="barcode" label="Code-barres" optional>
                        <x-form.input name="barcode" />
                    </x-form.field>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="price" label="Prix de vente (FCFA)" required>
                        <x-form.input name="price" type="number" min="0" step="1" inputmode="numeric" required />
                    </x-form.field>
                    <x-form.field name="cost_price" label="Coût d’achat (FCFA)" optional>
                        <x-form.input name="cost_price" type="number" min="0" step="1" inputmode="numeric" />
                    </x-form.field>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="stock_quantity" label="Stock initial" required>
                        <x-form.input name="stock_quantity" type="number" min="0" step="1" value="0" required />
                    </x-form.field>
                    <x-form.field name="reorder_threshold" label="Seuil d’alerte" optional>
                        <x-form.input name="reorder_threshold" type="number" min="0" step="1" />
                    </x-form.field>
                </div>
                <div class="flex justify-end gap-2">
                    <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'new-product' })">Annuler</x-ui.button>
                    <x-ui.button type="submit">Créer le produit</x-ui.button>
                </div>
            </form>
        </x-ui.dialog>
    @endcan
</x-app-layout>
