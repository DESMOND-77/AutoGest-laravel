<x-app-layout>
    <x-slot name="header">Commandes</x-slot>

    <div class="py-6 space-y-5 max-w-4xl mx-auto">
        @if (session('status'))
            <x-alert :variant="str_contains(session('status'), 'stock insuffisant') ? 'warning' : 'success'">{{ session('status') }}</x-alert>
        @endif
        @if ($errors->any())
            <x-alert variant="danger">{{ $errors->first() }}</x-alert>
        @endif

        <x-card>
            <div class="text-sm font-semibold text-content mb-3">Nouvelle vente</div>
            <form id="orders-create-form" method="POST" action="{{ route('store.orders.store') }}" class="space-y-3">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="student_id" value="Élève (optionnel)" />
                        <select id="student_id" name="student_id" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                            <option value="">-</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}">{{ $student->fullName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="customer_name" value="Nom du client (si vente comptoir)" />
                        <x-text-input id="customer_name" name="customer_name" class="block mt-1 w-full" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                    <div>
                        <x-input-label for="product" value="Produit" />
                        <select id="product" name="items[0][product_id]" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->stock_quantity }} en stock)</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="quantity" value="Quantité" />
                        <x-text-input id="quantity" type="number" name="items[0][quantity]" class="block mt-1 w-full" value="1" min="1" required />
                    </div>
                    <x-primary-button>Enregistrer la vente</x-primary-button>
                </div>
            </form>
        </x-card>

        <x-card :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-content-muted">
                        <tr>
                            <th class="px-5 py-3 font-medium">Date</th>
                            <th class="px-5 py-3 font-medium">Client</th>
                            <th class="px-5 py-3 font-medium">Articles</th>
                            <th class="px-5 py-3 font-medium">Total</th>
                            <th class="px-5 py-3 font-medium">Statut</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        @forelse ($orders as $order)
                            <tr class="hover:bg-surface-elevated/60 transition">
                                <td class="px-5 py-3 text-content-secondary">{{ $order->ordered_at->format('d/m/Y') }}</td>
                                <td class="px-5 py-3 text-content font-medium">{{ $order->student?->fullName() ?? $order->customer_name ?? '-' }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $order->items->map(fn ($i) => $i->product->name.' ×'.$i->quantity)->implode(', ') }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ number_format($order->total, 0, ',', ' ') }} FCFA</td>
                                <td class="px-5 py-3"><x-badge variant="info">{{ $order->status->label() }}</x-badge></td>
                            </tr>
                        @empty
                            <x-empty-table-row
                                colspan="5"
                                title="Aucune vente enregistrée."
                                message="Enregistrez une vente pour un élève ou un client de passage."
                                action="#orders-create-form"
                                action-label="Enregistrer une vente"
                            />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        {{ $orders->links() }}
    </div>
</x-app-layout>
