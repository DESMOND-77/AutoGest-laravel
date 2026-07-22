<?php

namespace App\Domain\Reports\Http\Controllers;

use App\Domain\Reports\Services\ReportService;
use App\Domain\Reports\Support\CsvExporter;
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
        $rows = $this->reports->revenueByMonth(12)
            ->map(fn (array $row) => [$row['month'], $row['total']]);

        return CsvExporter::stream('recettes-mensuelles.csv', ['Mois', 'Recettes (FCFA)'], $rows);
    }

    public function exportExamResultsCsv(): StreamedResponse
    {
        $summary = $this->reports->examResultsSummary();

        $rows = [
            ['Réussis', $summary['passed']],
            ['Échoués', $summary['failed']],
            ['En attente', $summary['pending']],
            ['Taux de réussite (%)', $summary['rate']],
        ];

        return CsvExporter::stream('resultats-examens.csv', ['Indicateur', 'Valeur'], $rows);
    }

    public function exportStudentsByStageCsv(): StreamedResponse
    {
        $rows = $this->reports->studentsByStage()
            ->map(fn (int $count, string $stage) => [$stage, $count])
            ->values();

        return CsvExporter::stream('eleves-par-etape.csv', ['Étape', "Nombre d'élèves"], $rows);
    }
}
