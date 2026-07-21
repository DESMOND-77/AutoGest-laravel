<?php

namespace App\Domain\Fleet\Services;

use App\Domain\Fleet\Models\Vehicle;
use Illuminate\Support\Collection;

/**
 * The legacy app computed "CT/assurance expire sous 30 jours" twice, in two
 * different ways: a UNION SQL query in admin/dashboard.php and a separate
 * PHP DateTime loop in admin/flotte.php — the same rule, liable to drift.
 * This is the one and only place that rule is expressed now; both the fleet
 * page and the admin dashboard call it.
 */
class AlertService
{
    public function __construct(
        private readonly int $warningWindowDays = 30,
    ) {}

    public function expiringSoon(): Collection
    {
        $threshold = now()->addDays($this->warningWindowDays)->toDateString();

        return Vehicle::query()
            ->where(function ($query) use ($threshold) {
                $query->whereNotNull('technical_inspection_expires_at')
                    ->where('technical_inspection_expires_at', '<=', $threshold);
            })
            ->orWhere(function ($query) use ($threshold) {
                $query->whereNotNull('insurance_expires_at')
                    ->where('insurance_expires_at', '<=', $threshold);
            })
            ->get();
    }

    public function count(): int
    {
        return $this->expiringSoon()->count();
    }
}
