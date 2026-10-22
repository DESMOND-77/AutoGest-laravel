{{-- Rapports : synthèse boutique. Attend la sortie de StoreReportService::dashboard(). --}}
<div class="flex flex-wrap justify-end gap-3 text-xs">
    <a href="{{ route('store.reports.top-products.csv') }}" class="text-primary hover:underline">Exporter le top produits (CSV)</a>
    <a href="{{ route('store.reports.pdf') }}" class="text-primary hover:underline">Télécharger le rapport (PDF)</a>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <x-kpi-card
        icon="currency"
        label="CA aujourd'hui"
        :value="number_format($revenueToday, 0, ',', ' ').' FCFA'"
    />
    <x-kpi-card
        icon="currency"
        label="CA ce mois"
        :value="number_format($revenueThisMonth, 0, ',', ' ').' FCFA'"
        :trend="number_format($revenueThisWeek, 0, ',', ' ').' FCFA cette semaine'"
    />
    <x-kpi-card
        icon="receipt"
        label="Ventes"
        :value="$salesCount"
    />
    <x-kpi-card
        icon="receipt"
        label="Encaissements en attente"
        :value="number_format($pendingBalance, 0, ',', ' ').' FCFA'"
    />
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
    <x-card :padded="false">
        <div class="px-5 py-3 border-b border-border/60 text-sm font-semibold text-content">Top produits</div>
        @if ($topProducts->isEmpty())
            <p class="text-sm text-content-muted p-6 text-center">Aucune vente enregistrée pour le moment.</p>
        @else
            <ul class="divide-y divide-border/60">
                @foreach ($topProducts as $row)
                    <li class="px-5 py-3 flex items-center justify-between gap-3 text-sm">
                        <div class="min-w-0">
                            <p class="text-content font-medium truncate">{{ $row['name'] }}</p>
                            <p class="text-content-muted text-xs">{{ $row['quantity'] }} vendu(s)</p>
                        </div>
                        <span class="text-content-secondary shrink-0">{{ number_format($row['revenue'], 0, ',', ' ') }} FCFA</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>

    <x-card :padded="false">
        <div class="px-5 py-3 border-b border-border/60 text-sm font-semibold text-content">Stocks critiques</div>
        @if ($criticalStock->isEmpty())
            <p class="text-sm text-content-muted p-6 text-center">Aucun produit sous son seuil de réapprovisionnement.</p>
        @else
            <ul class="divide-y divide-border/60">
                @foreach ($criticalStock as $product)
                    <li class="px-5 py-3 flex items-center justify-between gap-3 text-sm">
                        <div class="min-w-0">
                            <p class="text-content font-medium truncate">{{ $product->name }}</p>
                            <p class="text-content-muted text-xs">Seuil : {{ $product->reorder_threshold }}</p>
                        </div>
                        <x-badge :variant="$product->stock_quantity > 0 ? 'warning' : 'danger'">{{ $product->stock_quantity }} en stock</x-badge>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-card>
</div>
