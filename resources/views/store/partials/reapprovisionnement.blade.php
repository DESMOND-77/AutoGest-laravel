{{-- Réapprovisionnement : commandes fournisseurs + réception. Attend $suppliers, $catalogProducts, $purchaseOrders. --}}
<x-card>
    <div class="text-sm font-semibold text-content mb-3">Nouvelle commande fournisseur</div>
    <form id="purchase-orders-create-form" method="POST" action="{{ route('store.purchase-orders.store') }}" class="space-y-3">
        @csrf
        <div>
            <x-input-label for="supplier_id" value="Fournisseur" />
            <select id="supplier_id" name="supplier_id" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                @forelse ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @empty
                    <option value="">Aucun fournisseur enregistré</option>
                @endforelse
            </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
            <div>
                <x-input-label for="purchase_product_id" value="Produit" />
                <select id="purchase_product_id" name="items[0][product_id]" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm" required>
                    @forelse ($catalogProducts as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->stock_quantity }} en stock)</option>
                    @empty
                        <option value="">Aucun produit au catalogue</option>
                    @endforelse
                </select>
            </div>
            <div>
                <x-input-label for="purchase_quantity" value="Quantité" />
                <x-text-input id="purchase_quantity" type="number" name="items[0][quantity]" class="block mt-1 w-full" value="1" min="1" required />
            </div>
            <x-primary-button>Enregistrer la commande</x-primary-button>
        </div>
    </form>
</x-card>

<x-card :padded="false">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-content-muted">
                <tr>
                    <th class="px-5 py-3 font-medium">Date</th>
                    <th class="px-5 py-3 font-medium">Fournisseur</th>
                    <th class="px-5 py-3 font-medium">Articles</th>
                    <th class="px-5 py-3 font-medium">Statut</th>
                    <th class="px-5 py-3 font-medium">Réception</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border/60">
                @forelse ($purchaseOrders as $purchaseOrder)
                    <tr class="hover:bg-surface-elevated/60 transition align-top">
                        <td class="px-5 py-3 text-content-secondary">{{ $purchaseOrder->ordered_at->format('d/m/Y') }}</td>
                        <td class="px-5 py-3 text-content font-medium">{{ $purchaseOrder->supplier?->name ?? '-' }}</td>
                        <td class="px-5 py-3 text-content-secondary">{{ $purchaseOrder->items->map(fn ($i) => $i->product->name.' ×'.$i->quantity)->implode(', ') }}</td>
                        <td class="px-5 py-3"><x-badge :variant="$purchaseOrder->status->value === 'received' ? 'success' : ($purchaseOrder->status->value === 'partially_received' ? 'warning' : 'info')">{{ $purchaseOrder->status->label() }}</x-badge></td>
                        <td class="px-5 py-3">
                            <form method="POST" action="{{ route('store.purchase-orders.receive', $purchaseOrder) }}" class="space-y-2">
                                @csrf
                                @foreach ($purchaseOrder->items as $item)
                                    <label class="flex items-center justify-between gap-3 text-xs text-content-secondary">
                                        <span class="min-w-0">
                                            {{ $item->product->name }}
                                            <span class="text-content-muted">({{ $item->quantity_received }}/{{ $item->quantity }} reçus)</span>
                                        </span>
                                        <input
                                            type="number"
                                            name="received[{{ $item->product_id }}]"
                                            min="0"
                                            max="{{ max(0, $item->quantity - $item->quantity_received) }}"
                                            placeholder="0"
                                            class="w-16 shrink-0 rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm"
                                        />
                                    </label>
                                @endforeach
                                <x-secondary-button>Réceptionner</x-secondary-button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <x-empty-table-row
                        colspan="5"
                        title="Aucune commande fournisseur."
                        message="Passez une commande pour réapprovisionner votre stock."
                        action="#purchase-orders-create-form"
                        action-label="Passer une commande"
                    />
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

{{ $purchaseOrders->links() }}
