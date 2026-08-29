<?php

namespace App\Listeners;

use App\Domain\Finance\Enums\LedgerEntryType;
use App\Domain\Finance\Services\LedgerService;
use App\Domain\Recyclage\Events\RecyclageEntryRecorded;

/**
 * The integration point between Recyclage and Finance - deliberately
 * outside both app/Domain/Recyclage and app/Domain/Finance, so neither
 * domain has to know the other exists. Mirrors RecordVehicleExpenseInLedger
 * exactly (see FIN-04 in docs/audit/business-workflow.md).
 */
class RecordRecyclageEntryInLedger
{
    public function __construct(
        private readonly LedgerService $ledger,
    ) {}

    public function handle(RecyclageEntryRecorded $event): void
    {
        $this->ledger->recordManual([
            'type' => LedgerEntryType::Income->value,
            'amount' => $event->amount,
            'memo' => "{$event->entry->motif->label()} - {$event->fullName}",
            'occurred_on' => $event->sessionDate,
        ]);
    }
}
