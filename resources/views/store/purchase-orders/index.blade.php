<x-app-layout>
    <x-slot name="header">Commandes fournisseurs</x-slot>

    <div class="py-6 space-y-5 max-w-4xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif
        @if ($errors->any())
            <x-alert variant="danger">{{ $errors->first() }}</x-alert>
        @endif

        <x-card>
            <div class="text-sm font-semibold text-content mb-3">Nouvelle commande fournisseur</div>
            <form id="purchase-orders-create-form" method="POST" action="{{ route('store.purchase-orders.store') }}" class="space-y-3">
                @csrf
                <div>
                    <x-input-label for="supplier_id" value="Fournisseur" />
                    <select id="supplier_id" name="supplier_id" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                    <div>
                        <x-input-label for="product_id" value="Produit (ID)" />
                        <x-text-input id="product_id" type="number" name="items[0][product_id]" class="block mt-1 w-full" min="1" required />
                    </div>
                    <div>
                        <x-input-label for="quantity" value="Quantité" />
                        <x-text-input id="quantity" type="number" name="items[0][quantity]" class="block mt-1 w-full" value="1" min="1" required />
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
                            <tr class="hover:bg-surface-elevated/60 transition">
                                <td class="px-5 py-3 text-content-secondary">{{ $purchaseOrder->ordered_at->format('d/m/Y') }}</td>
                                <td class="px-5 py-3 text-content font-medium">{{ $purchaseOrder->supplier?->name ?? '-' }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $purchaseOrder->items->map(fn ($i) => $i->product->name.' ×'.$i->quantity)->implode(', ') }}</td>
                                <td class="px-5 py-3"><x-badge variant="info">{{ $purchaseOrder->status->label() }}</x-badge></td>
                                <td class="px-5 py-3">
                                    <form method="POST" action="{{ route('store.purchase-orders.receive', $purchaseOrder) }}" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        @foreach ($purchaseOrder->items as $item)
                                            <label class="flex items-center gap-1 text-content-secondary text-xs">
                                                {{ $item->product->name }}
                                                <input type="number" name="received[{{ $item->product_id }}]" min="0" class="w-16 rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm" />
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
    </div>
</x-app-layout>
