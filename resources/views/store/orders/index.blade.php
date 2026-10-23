<x-app-layout :title="'Ventes'">
    <x-layout.page-header
        title="Ventes"
        description="Enregistrez une vente au comptoir et décrémentez le stock automatiquement."
        :breadcrumbs="[['label' => 'Boutique', 'href' => route('store.index')], ['label' => 'Ventes']]"
    >
        <x-slot:actions>
            @can('create', \App\Domain\Store\Models\Order::class)
                <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-order' })">Nouvelle vente</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        <x-ui.card variant="solid" padding="p-0">
            @if ($orders->isEmpty())
                <x-ui.empty-state icon="shopping-bag" title="Aucune vente" description="Vendez un code, un livre ou un accessoire : le stock se met à jour tout seul." class="py-10">
                    <x-slot:actions>
                        @can('create', \App\Domain\Store\Models\Order::class)
                            <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-order' })">Nouvelle vente</x-ui.button>
                        @endcan
                    </x-slot:actions>
                </x-ui.empty-state>
            @else
                <div class="px-1 pb-1">
                    <x-data.table caption="Historique des ventes">
                        <thead>
                            <tr>
                                <th scope="col">Client</th>
                                <th scope="col">Date</th>
                                <th scope="col">Articles</th>
                                <th scope="col">Statut</th>
                                <th scope="col" class="text-right">Total</th>
                                <th scope="col"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    <td data-label="Client">
                                        @if ($order->student)
                                            <a href="{{ route('students.show', $order->student) }}" class="text-sm font-medium text-ink hover:text-accent">{{ $order->student->fullName() }}</a>
                                        @else
                                            <span class="text-sm text-ink">{{ $order->customer_name ?: 'Client comptoir' }}</span>
                                        @endif
                                    </td>
                                    <td data-label="Date"><x-ui.date :value="$order->ordered_at" /></td>
                                    <td data-label="Articles">
                                        <span class="text-[13px] text-ink-2">{{ $order->items->count() }} ligne(s)</span>
                                        <span class="block truncate text-[12px] text-ink-3">{{ $order->items->map(fn ($item) => $item->product?->name)->filter()->take(2)->join(', ') }}</span>
                                    </td>
                                    <td data-label="Statut"><x-ui.status :value="$order->status" compact /></td>
                                    <td data-label="Total" class="num"><x-ui.money :amount="$order->total" /></td>
                                    <td data-label="">
                                        <div class="flex justify-end">
                                            @can('cancel', $order)
                                                @if ($order->status?->value !== 'cancelled')
                                                    <form method="POST" action="{{ route('store.orders.cancel', $order) }}">
                                                        @csrf
                                                        <x-ui.button type="submit" size="sm" variant="ghost" class="text-danger" icon="ban">Annuler</x-ui.button>
                                                    </form>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-data.table>
                </div>
                <x-ui.pagination :paginator="$orders" label="Pagination des ventes" />
            @endif
        </x-ui.card>
    </div>

    @can('create', \App\Domain\Store\Models\Order::class)
        @php
            $catalog = $products->map(fn ($product) => [
                'id' => $product->id,
                'label' => $product->name.' — '.number_format((float) $product->price, 0, ',', ' ').' FCFA ('.$product->stock_quantity.' en stock)',
                'price' => (float) $product->price,
                'stock' => (int) $product->stock_quantity,
            ])->values()->all();
        @endphp

        <x-ui.dialog name="new-order" title="Nouvelle vente" size="lg">
            <x-form.errors />
            <form
                method="POST"
                action="{{ route('store.orders.store') }}"
                class="space-y-4"
                x-data="{
                    catalog: @js($catalog),
                    lines: [{ product_id: '', quantity: 1 }],
                    get total() {
                        return this.lines.reduce((sum, line) => {
                            const product = this.catalog.find((p) => String(p.id) === String(line.product_id));
                            return sum + (product ? product.price * (line.quantity || 0) : 0);
                        }, 0);
                    },
                    stockFor(productId) { return this.catalog.find((p) => String(p.id) === String(productId))?.stock ?? 0; },
                    add() { this.lines.push({ product_id: '', quantity: 1 }); },
                    remove(index) { if (this.lines.length > 1) this.lines.splice(index, 1); },
                }"
            >
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field name="student_id" label="Élève" optional hint="Laissez vide pour un client sans fiche.">
                        <x-form.select name="student_id" placeholder="Aucun élève" :options="$students->mapWithKeys(fn ($s) => [$s->id => $s->fullName()])->all()" />
                    </x-form.field>
                    <x-form.field name="customer_name" label="Nom du client" optional hint="Requis si aucun élève n’est sélectionné.">
                        <x-form.input name="customer_name" />
                    </x-form.field>
                </div>

                <div class="space-y-2">
                    <p class="text-sm font-medium text-ink">Articles</p>
                    <template x-for="(line, index) in lines" :key="index">
                        <div class="flex flex-wrap items-end gap-2">
                            <div class="min-w-0 flex-1">
                                <label :for="'product-' + index" class="sr-only">Produit</label>
                                <select :id="'product-' + index" :name="'items[' + index + '][product_id]'" x-model="line.product_id" required
                                    class="h-[var(--control-h)] w-full rounded-md border border-control bg-inset px-3 text-[14px] text-ink">
                                    <option value="">Choisir un produit</option>
                                    <template x-for="product in catalog" :key="product.id">
                                        <option :value="product.id" x-text="product.label"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="w-24">
                                <label :for="'qty-' + index" class="sr-only">Quantité</label>
                                <input :id="'qty-' + index" :name="'items[' + index + '][quantity]'" type="number" min="1" step="1"
                                    :max="stockFor(line.product_id) || null" x-model.number="line.quantity" required
                                    class="h-[var(--control-h)] w-full rounded-md border border-control bg-inset px-3 text-[14px] text-ink">
                            </div>
                            <button type="button" x-on:click="remove(index)" class="grid size-10 place-items-center rounded-full text-ink-3 hover:bg-hover hover:text-danger" aria-label="Retirer cette ligne">
                                <x-ui.icon name="trash-2" class="w-4 h-4" />
                            </button>
                        </div>
                    </template>
                    <button type="button" x-on:click="add()" class="text-[13px] font-medium text-accent hover:underline">+ Ajouter un article</button>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-line pt-3">
                    <p class="text-[13px] text-ink-2">Total : <span class="font-semibold text-ink" x-text="new Intl.NumberFormat('fr-GA', { maximumFractionDigits: 0 }).format(total) + ' FCFA'"></span></p>
                    <div class="flex gap-2">
                        <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'new-order' })">Annuler</x-ui.button>
                        <x-ui.button type="submit">Enregistrer la vente</x-ui.button>
                    </div>
                </div>
            </form>
        </x-ui.dialog>
    @endcan
</x-app-layout>
