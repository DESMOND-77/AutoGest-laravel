@php
    $money = fn ($value) => number_format((float) $value, 0, ',', ' ').' FCFA';
    $generatedAt = now();
@endphp

<x-pdf-layout
    title="Rapport boutique"
    :subtitle="config('app.name', 'Auto-GestBoard').' · rapport d’activité boutique'"
    reference="Rapport boutique"
    :issuedAt="$generatedAt->format('d/m/Y à H:i')"
>
    <h2>Chiffre d’affaires</h2>
    <table>
        <tr>
            <td style="width: 25%;"><span class="muted" style="font-size: 9px; text-transform: uppercase;">Aujourd’hui</span><br><strong>{{ $money($revenueToday) }}</strong></td>
            <td style="width: 25%;"><span class="muted" style="font-size: 9px; text-transform: uppercase;">Cette semaine</span><br><strong>{{ $money($revenueThisWeek) }}</strong></td>
            <td style="width: 25%;"><span class="muted" style="font-size: 9px; text-transform: uppercase;">Ce mois</span><br><strong>{{ $money($revenueThisMonth) }}</strong></td>
            <td style="width: 25%;"><span class="muted" style="font-size: 9px; text-transform: uppercase;">Cette année</span><br><strong>{{ $money($revenueThisYear) }}</strong></td>
        </tr>
    </table>

    <table style="margin-top: 8px;">
        <tr>
            <td style="width: 50%;"><span class="muted">Nombre de ventes</span> — <strong>{{ $salesCount }}</strong></td>
            <td style="width: 50%;"><span class="muted">Solde en attente</span> — <strong>{{ $money($pendingBalance) }}</strong></td>
        </tr>
    </table>

    <h2>Meilleures ventes</h2>
    @if (count($topProducts))
        <table>
            <thead>
                <tr>
                    <th style="width: 52%;">Produit</th>
                    <th class="num" style="width: 16%;">Quantité</th>
                    <th class="num" style="width: 32%;">Chiffre d’affaires</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($topProducts as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td class="num">{{ number_format((int) $row['quantity'], 0, ',', ' ') }}</td>
                        <td class="num">{{ $money($row['revenue']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">Aucune vente enregistrée sur la période.</p>
    @endif

    <h2>Stocks à réapprovisionner</h2>
    @if (count($criticalStock))
        <table>
            <thead>
                <tr>
                    <th style="width: 52%;">Produit</th>
                    <th class="num" style="width: 24%;">Stock</th>
                    <th class="num" style="width: 24%;">Seuil</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($criticalStock as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td class="num">{{ (int) $product->stock_quantity }}</td>
                        <td class="num">{{ (int) $product->reorder_threshold }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">Aucun produit sous son seuil de réapprovisionnement.</p>
    @endif

    <p class="footer">
        Document interne — {{ config('app.name', 'Auto-GestBoard') }}. Les montants sont exprimés en francs CFA (XAF) et arrondis à l’unité.
    </p>
</x-pdf-layout>
