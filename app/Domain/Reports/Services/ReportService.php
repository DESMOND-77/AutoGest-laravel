<?php

namespace App\Domain\Reports\Services;

use App\Domain\Finance\Enums\LedgerEntryType;
use App\Domain\Finance\Models\LedgerEntry;
use App\Domain\Fleet\Services\AlertService;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\Student;
use App\Domain\Training\Enums\ExamResult;
use App\Domain\Training\Models\Exam;
use Illuminate\Support\Collection;

/**
 * Read-only aggregation across Finance, Training, Fleet and Students —
 * the one place the admin dashboard's numbers come from, replacing the
 * legacy pattern of the same "recettes du mois" / "CT sous 30 jours" query
 * being written slightly differently in dashboard.php and again in each
 * module's own page (see fixs.md #7).
 */
class ReportService
{
    public function __construct(
        private readonly AlertService $fleetAlerts,
    ) {}

    /**
     * @return Collection<int, array{month: string, total: float}>
     */
    public function revenueByMonth(int $months = 6): Collection
    {
        $since = now()->subMonths($months - 1)->startOfMonth();

        $rows = LedgerEntry::query()
            ->where('type', LedgerEntryType::Income->value)
            ->where('occurred_on', '>=', $since->toDateString())
            ->selectRaw("DATE_FORMAT(occurred_on, '%Y-%m') as month, SUM(amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        return collect(range(0, $months - 1))
            ->map(fn (int $i) => $since->copy()->addMonths($i))
            ->map(fn ($date) => [
                'month' => $date->translatedFormat('M Y'),
                'total' => (float) ($rows->get($date->format('Y-m'))->total ?? 0),
            ]);
    }

    /**
     * @return array{passed: int, failed: int, pending: int, rate: float}
     */
    public function examResultsSummary(): array
    {
        $counts = Exam::query()
            ->selectRaw('result, count(*) as total')
            ->groupBy('result')
            ->pluck('total', 'result');

        $passed = (int) ($counts[ExamResult::Passed->value] ?? 0);
        $failed = (int) ($counts[ExamResult::Failed->value] ?? 0);
        $pending = (int) ($counts[ExamResult::Pending->value] ?? 0);
        $decided = $passed + $failed;

        return [
            'passed' => $passed,
            'failed' => $failed,
            'pending' => $pending,
            'rate' => $decided > 0 ? round($passed / $decided * 100, 1) : 0.0,
        ];
    }

    /**
     * @return Collection<string, int>
     */
    public function studentsByStage(): Collection
    {
        $counts = Student::query()
            ->selectRaw('lifecycle_stage, count(*) as total')
            ->groupBy('lifecycle_stage')
            ->pluck('total', 'lifecycle_stage');

        return collect(LifecycleStage::cases())
            ->mapWithKeys(fn (LifecycleStage $stage) => [
                $stage->label() => (int) ($counts[$stage->value] ?? 0),
            ]);
    }

    public function fleetAlertCount(): int
    {
        return $this->fleetAlerts->count();
    }
}
