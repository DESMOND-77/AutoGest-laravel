<?php

namespace App\Listeners;

use App\Domain\Finance\Enums\LedgerEntryType;
use App\Domain\Finance\Services\LedgerService;
use App\Domain\Fleet\Events\VehicleExpenseRecorded;

/**
 * The integration point between Fleet and Finance — deliberately outside
 * both app/Domain/Fleet and app/Domain/Finance, so neither domain has to
 * know the other exists. This is what the legacy admin/flotte.php skipped by
 * writing straight into the `transactions` table from the vehicle page.
 */
class RecordVehicleExpenseInLedger
{
    public function __construct(
        private readonly LedgerService $ledger,
    ) {}

    public function handle(VehicleExpenseRecorded $event): void
    {
        $this->ledger->recordManual([
            'type' => LedgerEntryType::Expense->value,
            'amount' => $event->amount,
            'memo' => $event->memo,
            'occurred_on' => $event->occurredOn,
        ]);
    }
}
