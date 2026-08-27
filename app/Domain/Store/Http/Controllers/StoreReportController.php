<?php

namespace App\Domain\Store\Http\Controllers;

use App\Domain\Store\Models\Order;
use App\Domain\Store\Services\StoreReportService;
use App\Http\Controllers\Controller;
use App\Support\CsvExporter;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoreReportController extends Controller
{
    public function __construct(
        private readonly StoreReportService $reports,
    ) {}

    public function show(): View
    {
        $this->authorize('viewAny', Order::class);

        return view('store.reports.show', $this->reports->dashboard());
    }

    public function exportTopProductsCsv(): StreamedResponse
    {
        $rows = $this->reports->dashboard()['topProducts']
            ->map(fn (array $row) => [$row['name'], $row['quantity'], $row['revenue']]);

        return CsvExporter::stream('top-produits.csv', ['Produit', 'Quantité vendue', 'Chiffre d\'affaires (FCFA)'], $rows);
    }
}
