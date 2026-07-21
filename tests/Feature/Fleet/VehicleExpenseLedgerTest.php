<?php

use App\Domain\Finance\Enums\LedgerEntryType;
use App\Domain\Finance\Models\LedgerEntry;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Fleet\Services\AlertService;
use App\Domain\Fleet\Services\FleetService;
use App\Domain\Tenancy\Models\Structure;
use App\Support\TenantContext;

/**
 * Regression coverage for the legacy admin/flotte.php anti-pattern flagged
 * in the architecture proposal: a vehicle page that reaches directly into
 * the financial `transactions` table. Here Fleet only ever raises
 * VehicleExpenseRecorded; RecordVehicleExpenseInLedger (outside both
 * domains) is what turns it into a LedgerEntry.
 */
it('journals a maintenance cost to the ledger without Fleet depending on Finance', function () {
    $structure = Structure::factory()->create();
    TenantContext::set($structure);

    $vehicle = Vehicle::factory()->create(['structure_id' => $structure->id, 'mileage' => 10000]);

    app(FleetService::class)->logMaintenance($vehicle, [
        'type' => 'vidange',
        'cost' => 35000,
        'mileage' => 10500,
        'performed_on' => now()->toDateString(),
    ]);

    $entry = LedgerEntry::query()->sole();
    expect((float) $entry->amount)->toBe(35000.0);
    expect($entry->type)->toBe(LedgerEntryType::Expense);
    expect($vehicle->fresh()->mileage)->toBe(10500);

    TenantContext::clear();
});

it('computes expiring-soon vehicles from a single alert rule', function () {
    $structure = Structure::factory()->create();
    TenantContext::set($structure);

    $expiring = Vehicle::factory()->create([
        'structure_id' => $structure->id,
        'technical_inspection_expires_at' => now()->addDays(10)->toDateString(),
    ]);
    $safe = Vehicle::factory()->create([
        'structure_id' => $structure->id,
        'technical_inspection_expires_at' => now()->addDays(90)->toDateString(),
    ]);

    $result = app(AlertService::class)->expiringSoon();

    expect($result->pluck('id'))->toContain($expiring->id);
    expect($result->pluck('id'))->not->toContain($safe->id);

    TenantContext::clear();
});
