<?php

namespace App\Domain\Fleet\Events;

use App\Domain\Fleet\Models\Vehicle;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Raised whenever a maintenance or fuel log carries a cost. Fleet must not
 * depend on Finance (see the domain diagram in the migration proposal), so
 * this event is Fleet's entire contribution to bookkeeping — turning it into
 * a LedgerEntry is somebody else's job. Compare to the legacy
 * admin/flotte.php, which wrote directly into the `transactions` table from
 * inside the vehicle page.
 */
class VehicleExpenseRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Vehicle $vehicle,
        public readonly float $amount,
        public readonly string $memo,
        public readonly string $occurredOn,
    ) {}
}
