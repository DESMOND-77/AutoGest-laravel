<?php

namespace App\Domain\Recyclage\Events;

use App\Domain\Recyclage\Models\RecyclageEntry;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Recyclage's entire contribution to bookkeeping - turning this into a
 * LedgerEntry is somebody else's job (see RecordRecyclageEntryInLedger),
 * exactly mirroring App\Domain\Fleet\Events\VehicleExpenseRecorded. Recyclage
 * must never depend on Finance directly.
 */
class RecyclageEntryRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly RecyclageEntry $entry,
        public readonly float $amount,
        public readonly string $fullName,
        public readonly string $sessionDate,
    ) {}
}
