<?php

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Models\LedgerEntry;
use App\Models\User;

/**
 * Manual journal entries not tied to a student invoice (salaries, bank
 * deposits/withdrawals, miscellaneous expenses) - the equivalent of the
 * legacy facturation.php "entrées/sorties/banque" tabs.
 */
class LedgerService
{
    public function recordManual(array $data, ?User $createdBy = null): LedgerEntry
    {
        return LedgerEntry::query()->create([
            'created_by' => $createdBy?->id,
            'type' => $data['type'],
            'amount' => $data['amount'],
            'memo' => $data['memo'] ?? null,
            'occurred_on' => $data['occurred_on'] ?? now()->toDateString(),
        ]);
    }
}
