<x-app-layout :title="'Rapports boutique'">
    <x-layout.page-header
        title="Rapports boutique"
        description="Chiffre d’affaires, meilleures ventes et stock critique."
        :breadcrumbs="[['label' => 'Boutique', 'href' => route('store.index')], ['label' => 'Rapports']]"
    >
        <x-slot:actions>
            <x-ui.button :href="route('store.reports.top-products.csv')" variant="secondary" icon="download">CSV</x-ui.button>
            <x-ui.button :href="route('store.reports.pdf')" variant="secondary" icon="printer">PDF</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="space-y-4">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-data.kpi label="Aujourd’hui" :value="number_format((float) $revenueToday, 0, ',', ' ').' FCFA'" icon="wallet" />
            <x-data.kpi label="Cette semaine" :value="number_format((float) $revenueThisWeek, 0, ',', ' ').' FCFA'" icon="calendar" />
            <x-data.kpi label="Ce mois" :value="number_format((float) $revenueThisMonth, 0, ',', ' ').' FCFA'" icon="chart-bar" />
            <x-data.kpi label="Cette année" :value="number_format((float) $revenueThisYear, 0, ',', ' ').' FCFA'" icon="trending-up" />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-ui.card title="Meilleures ventes" :description="$salesCount.' vente(s) enregistrée(s)'" variant="solid" padding="p-0">
                @if ($topProducts->isEmpty())
                    <x-ui.empty-state icon="chart-bar" title="Aucune vente" description="Les statistiques apparaîtront après la première vente." class="py-8" />
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($topProducts as $row)
                            <li class="flex items-center justify-between gap-2 px-5 py-2.5">
                                <span class="min-w-0 truncate text-[13px] text-ink">{{ $row['name'] }}</span>
                                <span class="shrink-0 text-[12.5px] text-ink-3">{{ $row['quantity'] }} vendu(s)</span>
                                <x-ui.money :amount="$row['revenue']" class="text-[13px] font-semibold text-ink" />
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>

            <x-ui.card title="Stock critique" description="Produits sous leur seuil de réapprovisionnement." variant="solid" padding="p-0">
                @if ($criticalStock->isEmpty())
                    <x-ui.empty-state icon="circle-check" title="Stock sain" description="Aucun produit n’est sous son seuil d’alerte." class="py-8" />
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($criticalStock as $product)
                            <li class="flex items-center justify-between gap-2 px-5 py-2.5">
                                <span class="min-w-0 truncate text-[13px] text-ink">{{ $product->name }}</span>
                                <x-ui.badge tone="warning">{{ $product->stock_quantity }} / {{ $product->reorder_threshold }}</x-ui.badge>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>

        <x-ui.card variant="panel">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-[13px] text-ink-2">Encaissements en attente sur les ventes à crédit</p>
                    <p class="text-xl font-bold text-ink"><x-ui.money :amount="$pendingBalance" /></p>
                </div>
                <x-ui.button :href="route('finance.invoices.index')" variant="secondary" size="sm">Voir les factures</x-ui.button>
            </div>
        </x-ui.card>
    </div>
</x-app-layout>
