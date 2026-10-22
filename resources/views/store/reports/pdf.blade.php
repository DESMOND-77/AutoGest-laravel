<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
    </style>
</head>
<body>
    <h1>Rapport Boutique</h1>
    <p>Généré le {{ now()->format('d/m/Y') }}</p>

    <h2>Chiffre d'affaires</h2>
    <table>
        <tr><th>Aujourd'hui</th><td>{{ number_format($revenueToday, 0, ',', ' ') }} FCFA</td></tr>
        <tr><th>Cette semaine</th><td>{{ number_format($revenueThisWeek, 0, ',', ' ') }} FCFA</td></tr>
        <tr><th>Ce mois</th><td>{{ number_format($revenueThisMonth, 0, ',', ' ') }} FCFA</td></tr>
        <tr><th>Cette année</th><td>{{ number_format($revenueThisYear, 0, ',', ' ') }} FCFA</td></tr>
    </table>

    <h2>Top produits</h2>
    <table>
        <tr><th>Produit</th><th>Quantité</th><th>CA</th></tr>
        @foreach ($topProducts as $row)
            <tr><td>{{ $row['name'] }}</td><td>{{ $row['quantity'] }}</td><td>{{ number_format($row['revenue'], 0, ',', ' ') }} FCFA</td></tr>
        @endforeach
    </table>

    <h2>Stocks critiques</h2>
    <table>
        <tr><th>Produit</th><th>Stock actuel</th><th>Seuil</th></tr>
        @foreach ($criticalStock as $product)
            <tr><td>{{ $product->name }}</td><td>{{ $product->stock_quantity }}</td><td>{{ $product->reorder_threshold }}</td></tr>
        @endforeach
    </table>
</body>
</html>
