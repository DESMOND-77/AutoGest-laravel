@php
    $critical = $allProducts->filter(fn ($product) => $product->reorder_threshold && $product->stock_quantity < $product->reorder_threshold);
    $stockValue = $allProducts->sum(fn ($product) => (float) $product->price * (int) $product->stock_quantity);
    $openOrders = $orders->getCollection()->where('status.value', '!=', 'cancelled');
@endphp

<x-app-layout :title="'Boutique'">
    <x-layout.page-header
        title="Boutique"
        description="Codes, livres et accessoires : vente au comptoir, stock et réapprovisionnement."
        :breadcrumbs="[['label' => 'Boutique']]"
    >
        <x-slot:actions>
            <x-ui.button :href="route('store.orders.index')" variant="secondary" icon="receipt">Ventes</x-ui.button>
            <x-ui.button :href="route('store.products.index')" variant="secondary" icon="package">Produits</x-ui.button>
            <x-ui.button :href="route('store.reports.show')" icon="chart-bar">Rapports</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-data.kpi label="Produits actifs" :value="$allProducts->where('active', true)->count()" icon="package" />
            <x-data.kpi label="Stock à réapprovisionner" :value="$critical->count()" icon="circle-alert" :hint="$critical->isEmpty() ? 'Aucun seuil franchi' : 'Sous le seuil configuré'" />
            <x-data.kpi label="Valeur du stock" :value="number_format((float) $stockValue, 0, ',', ' ').' FCFA'" icon="wallet" />
            <x-data.kpi label="Ventes récentes" :value="$orders->total()" icon="shopping-bag" />
        </div>

        @if ($critical->isNotEmpty())
            <x-ui.card title="Stock critique" description="Produits sous leur seuil de réapprovisionnement." variant="solid" padding="p-0">
                <ul class="divide-y divide-line">
                    @foreach ($critical as $product)
                        <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-[13.5px] font-medium text-ink">{{ $product->name }}</p>
                                <p class="text-[12px] text-ink-3">{{ $product->sku ?: 'Sans référence' }} · seuil {{ $product->reorder_threshold }}</p>
                            </div>
                            <x-ui.badge tone="warning">{{ $product->stock_quantity }} en stock</x-ui.badge>
                        </li>
                    @endforeach
                </ul>
                <div class="border-t border-line px-5 py-2.5">
                    <a href="{{ route('store.purchase-orders.index') }}" class="text-[13px] font-medium text-accent hover:underline">Créer un bon de commande</a>
                </div>
            </x-ui.card>
        @endif

        <div class="grid gap-4 lg:grid-cols-2">
            <x-ui.card :title="'Dernières ventes'" variant="solid" padding="p-0">
                @if ($orders->isEmpty())
                    <x-ui.empty-state icon="shopping-bag" title="Aucune vente" description="Enregistrez une vente depuis la page des ventes." class="py-8" />
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($orders->take(6) as $order)
                            <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                                <div class="min-w-0">
                                    <p class="truncate text-[13px] font-medium text-ink">
                                        {{ $order->student?->fullName() ?? ($order->customer_name ?: 'Client comptoir') }}
                                    </p>
                                    <p class="text-[12px] text-ink-3"><x-ui.date :value="$order->ordered_at" /> · {{ $order->items->count() }} article(s)</p>
                                </div>
                                <x-ui.money :amount="$order->total" class="text-[13px] font-semibold text-ink" />
                            </li>
                        @endforeach
                    </ul>
                    <div class="border-t border-line px-5 py-2.5">
                        <a href="{{ route('store.orders.index') }}" class="text-[13px] font-medium text-accent hover:underline">Toutes les ventes</a>
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card title="Réapprovisionnements" variant="solid" padding="p-0">
                @if ($purchaseOrders->isEmpty())
                    <x-ui.empty-state icon="truck" title="Aucun bon de commande" description="Commandez vos fournisseurs et réceptionnez le stock en un geste." class="py-8" />
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($purchaseOrders->take(6) as $order)
                            <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                                <div class="min-w-0">
                                    <p class="truncate text-[13px] font-medium text-ink">{{ $order->supplier?->name ?? 'Fournisseur non renseigné' }}</p>
                                    <p class="text-[12px] text-ink-3"><x-ui.date :value="$order->ordered_at" /> · {{ $order->items->count() }} ligne(s)</p>
                                </div>
                                <x-ui.status :value="$order->status" compact />
                            </li>
                        @endforeach
                    </ul>
                    <div class="border-t border-line px-5 py-2.5">
                        <a href="{{ route('store.purchase-orders.index') }}" class="text-[13px] font-medium text-accent hover:underline">Tous les bons</a>
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
