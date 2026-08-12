<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Catalogue boutique
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Nouveau produit</div>
                <form id="products-create-form" method="POST" action="{{ route('store.products.store') }}" class="grid grid-cols-4 gap-3 items-end">
                    @csrf
                    <x-text-input name="name" placeholder="Nom" class="block w-full" required />
                    <x-text-input name="category" placeholder="Catégorie" class="block w-full" />
                    <x-text-input type="number" step="0.01" name="price" placeholder="Prix" class="block w-full" required />
                    <x-text-input type="number" name="stock_quantity" placeholder="Stock" class="block w-full" required />
                    <div class="col-span-4">
                        <x-primary-button>Ajouter</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr><th class="px-4 py-3">Nom</th><th class="px-4 py-3">Catégorie</th><th class="px-4 py-3">Prix</th><th class="px-4 py-3">Stock</th><th class="px-4 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($products as $product)
                            <tr>
                                <td class="px-4 py-3">{{ $product->name }}</td>
                                <td class="px-4 py-3">{{ $product->category ?? '—' }}</td>
                                <td class="px-4 py-3">{{ number_format($product->price, 0, ',', ' ') }} FCFA</td>
                                <td class="px-4 py-3">{{ $product->stock_quantity }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('store.products.destroy', $product) }}" onsubmit="return confirm('Supprimer ?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600 underline">Supprimer</button>
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
            </div>
        </div>
    </div>
</x-app-layout>
