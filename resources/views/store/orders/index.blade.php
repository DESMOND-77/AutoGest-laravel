<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Commandes
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-100 text-green-800 text-sm rounded-md p-3">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="bg-red-100 text-red-800 text-sm rounded-md p-3">{{ $errors->first() }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Nouvelle vente</div>
                <form method="POST" action="{{ route('store.orders.store') }}" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="student_id" value="Élève (optionnel)" />
                            <select id="student_id" name="student_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm block mt-1 w-full">
                                <option value="">—</option>
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

                    <div class="grid grid-cols-3 gap-3 items-end">
                        <div>
                            <x-input-label for="product" value="Produit" />
                            <select id="product" name="items[0][product_id]" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm block mt-1 w-full">
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
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                        <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Client</th><th class="px-4 py-3">Articles</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Statut</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($orders as $order)
                            <tr>
                                <td class="px-4 py-3">{{ $order->ordered_at->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">{{ $order->student?->fullName() ?? $order->customer_name ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $order->items->map(fn ($i) => $i->product->name.' ×'.$i->quantity)->implode(', ') }}</td>
                                <td class="px-4 py-3">{{ number_format($order->total, 0, ',', ' ') }} FCFA</td>
                                <td class="px-4 py-3">{{ $order->status->label() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Aucune commande.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            {{ $orders->links() }}
        </div>
    </div>
</x-app-layout>
