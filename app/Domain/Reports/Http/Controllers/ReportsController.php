<?php

namespace App\Domain\Reports\Http\Controllers;

use App\Domain\Reports\Services\ReportService;
use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
    ) {}

    public function dashboard(): View
    {
        return view('admin.dashboard', [
            'revenueByMonth' => $this->reports->revenueByMonth(),
            'examStats' => $this->reports->examResultsSummary(),
            'studentsByStage' => $this->reports->studentsByStage(),
            'fleetAlertCount' => $this->reports->fleetAlertCount(),
        ]);
    }

    public function exportRevenueCsv(): StreamedResponse
    {
        $rows = $this->reports->revenueByMonth(12);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Mois', 'Recettes (FCFA)']);

            foreach ($rows as $row) {
                fputcsv($handle, [$row['month'], $row['total']]);
            }

            fclose($handle);
        }, 'recettes-mensuelles.csv');
    }
}
