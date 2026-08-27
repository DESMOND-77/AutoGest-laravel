{{-- Produits : catalogue boutique. Attend $catalogProducts. --}}
<x-card>
    <div class="text-sm font-semibold text-content mb-3">Nouveau produit</div>
    <form id="products-create-form" method="POST" action="{{ route('store.products.store') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
        @csrf
        <x-text-input name="name" placeholder="Nom" class="block w-full" required />
        <x-text-input name="category" placeholder="Catégorie" class="block w-full" />
        <x-text-input type="number" step="0.01" name="price" placeholder="Prix" class="block w-full" required />
        <x-text-input type="number" name="stock_quantity" placeholder="Stock" class="block w-full" required />
        <div class="sm:col-span-4">
            <x-primary-button>Ajouter</x-primary-button>
        </div>
    </form>
</x-card>

<x-card :padded="false">
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="text-content-muted">
                <tr>
                    <th class="px-5 py-3 font-medium">Nom</th>
                    <th class="px-5 py-3 font-medium">Catégorie</th>
                    <th class="px-5 py-3 font-medium">Prix</th>
                    <th class="px-5 py-3 font-medium">Stock</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border/60">
                @forelse ($catalogProducts as $product)
                    <tr class="hover:bg-surface-elevated/60 transition">
                        <td class="px-5 py-3 text-content font-medium">{{ $product->name }}</td>
                        <td class="px-5 py-3 text-content-secondary">{{ $product->category ?? '-' }}</td>
                        <td class="px-5 py-3 text-content-secondary">{{ number_format($product->price, 0, ',', ' ') }} FCFA</td>
                        <td class="px-5 py-3">
                            <x-badge :variant="$product->stock_quantity > 0 ? 'success' : 'danger'">{{ $product->stock_quantity }}</x-badge>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <form method="POST" action="{{ route('store.products.destroy', $product) }}" onsubmit="return confirm('Supprimer ?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-danger hover:underline">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <x-empty-table-row
                        colspan="5"
                        title="Aucun produit en boutique."
                        message="Ajoutez un produit pour commencer à gérer votre stock et vos ventes."
                        action="#products-create-form"
                        action-label="Ajouter un produit"
                    />
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
