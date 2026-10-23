<x-app-layout :title="'Bons de commande'">
    <x-layout.page-header
        title="Bons de commande"
        description="Commandez vos fournisseurs, puis réceptionnez les quantités reçues pour mettre à jour le stock."
        :breadcrumbs="[['label' => 'Boutique', 'href' => route('store.index')], ['label' => 'Bons de commande']]"
    >
        <x-slot:actions>
            @can('create', \App\Domain\Store\Models\PurchaseOrder::class)
                <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-purchase' })">Nouveau bon</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        @if ($suppliers->isEmpty())
            <x-ui.alert tone="warning" title="Aucun fournisseur enregistré">
                Ajoutez d’abord un fournisseur depuis <a href="{{ route('store.products.index') }}" class="font-medium underline">Produits</a> pour créer un bon de commande.
            </x-ui.alert>
        @endif

        <x-ui.card variant="solid" padding="p-0">
            @if ($purchaseOrders->isEmpty())
                <x-ui.empty-state icon="truck" title="Aucun bon de commande" description="Un bon de commande suit les quantités commandées et reçues, produit par produit." class="py-10">
                    <x-slot:actions>
                        @can('create', \App\Domain\Store\Models\PurchaseOrder::class)
                            @if ($suppliers->isNotEmpty())
                                <x-ui.button icon="plus" x-on:click="$dispatch('open-dialog', { name: 'new-purchase' })">Nouveau bon</x-ui.button>
                            @endif
                        @endcan
                    </x-slot:actions>
                </x-ui.empty-state>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($purchaseOrders as $order)
                        <li class="px-5 py-3">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-[13.5px] font-semibold text-ink">Bon #{{ $order->id }} · {{ $order->supplier?->name ?? 'Fournisseur inconnu' }}</p>
                                    <p class="text-[12px] text-ink-3"><x-ui.date :value="$order->ordered_at" /> · {{ $order->items->count() }} ligne(s)</p>
                                </div>
                                <x-ui.status :value="$order->status" compact />
                            </div>

                            <ul class="mt-2 space-y-1">
                                @foreach ($order->items as $item)
                                    <li class="flex items-center justify-between gap-2 text-[12.5px]">
                                        <span class="min-w-0 truncate text-ink-2">{{ $item->product?->name ?? 'Produit supprimé' }}</span>
                                        <span class="shrink-0 tabular-nums text-ink-3">commandé {{ $item->quantity }} · reçu {{ $item->quantity_received }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            @can('update', $order)
                                @if ($order->status?->value !== 'received' && $order->status?->value !== 'cancelled')
                                    <form method="POST" action="{{ route('store.purchase-orders.receive', $order) }}" class="mt-3 space-y-2 border-t border-line pt-2.5">
                                        @csrf
                                        <p class="text-[12.5px] font-medium text-ink">Réceptionner</p>
                                        <div class="grid gap-2 sm:grid-cols-2">
                                            @foreach ($order->items as $item)
                                                <label class="flex items-center gap-2 text-[12.5px] text-ink-2">
                                                    <span class="min-w-0 flex-1 truncate">{{ $item->product?->name }}</span>
                                                    <input type="number" name="received[{{ $item->id }}]" min="0" step="1" :max="{{ $item->quantity }}"
                                                        value="{{ max(0, $item->quantity - $item->quantity_received) }}"
                                                        class="h-9 w-20 rounded-md border border-control bg-inset px-2 text-[13px] text-ink"
                                                        aria-label="Quantité reçue pour {{ $item->product?->name }}">
                                                </label>
                                            @endforeach
                                        </div>
                                        <x-ui.button type="submit" size="sm" variant="secondary" icon="check">Enregistrer la réception</x-ui.button>
                                    </form>
                                @endif
                            @endcan
                        </li>
                    @endforeach
                </ul>
                <x-ui.pagination :paginator="$purchaseOrders" label="Pagination des bons de commande" />
            @endif
        </x-ui.card>
    </div>

    @can('create', \App\Domain\Store\Models\PurchaseOrder::class)
        @if ($suppliers->isNotEmpty())
            <x-ui.dialog name="new-purchase" title="Nouveau bon de commande" size="lg">
                <x-form.errors />
                <form method="POST" action="{{ route('store.purchase-orders.store') }}" class="space-y-4"
                    x-data="{ lines: [{ product_id: '', quantity: 1 }], add() { this.lines.push({ product_id: '', quantity: 1 }); }, remove(i) { if (this.lines.length > 1) this.lines.splice(i, 1); } }">
                    @csrf
                    <x-form.field name="supplier_id" label="Fournisseur" required>
                        <x-form.select name="supplier_id" :options="$suppliers->pluck('name', 'id')->all()" placeholder="Choisir un fournisseur" required />
                    </x-form.field>

                    <div class="space-y-2">
                        <p class="text-sm font-medium text-ink">Produits à commander</p>
                        <template x-for="(line, index) in lines" :key="index">
                            <div class="flex flex-wrap items-end gap-2">
                                <div class="min-w-0 flex-1">
                                    <label :for="'po-product-' + index" class="sr-only">Produit</label>
                                    <select :id="'po-product-' + index" :name="'items[' + index + '][product_id]'" x-model="line.product_id" required
                                        class="h-[var(--control-h)] w-full rounded-md border border-control bg-inset px-3 text-[14px] text-ink">
                                        <option value="">Choisir un produit</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->stock_quantity }} en stock)</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="w-24">
                                    <label :for="'po-qty-' + index" class="sr-only">Quantité</label>
                                    <input :id="'po-qty-' + index" :name="'items[' + index + '][quantity]'" type="number" min="1" step="1" x-model.number="line.quantity" required
                                        class="h-[var(--control-h)] w-full rounded-md border border-control bg-inset px-3 text-[14px] text-ink">
                                </div>
                                <button type="button" x-on:click="remove(index)" class="grid size-10 place-items-center rounded-full text-ink-3 hover:bg-hover hover:text-danger" aria-label="Retirer cette ligne">
                                    <x-ui.icon name="trash-2" class="w-4 h-4" />
                                </button>
                            </div>
                        </template>
                        <button type="button" x-on:click="add()" class="text-[13px] font-medium text-accent hover:underline">+ Ajouter un produit</button>
                    </div>

                    <div class="flex justify-end gap-2">
                        <x-ui.button variant="ghost" type="button" x-on:click="$dispatch('close-dialog', { name: 'new-purchase' })">Annuler</x-ui.button>
                        <x-ui.button type="submit">Créer le bon</x-ui.button>
                    </div>
                </form>
            </x-ui.dialog>
        @endif
    @endcan
</x-app-layout>
