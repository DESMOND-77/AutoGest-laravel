<?php

use App\Domain\Finance\Models\LedgerEntry;
use App\Domain\Finance\Services\LedgerService;
use App\Domain\Recyclage\Enums\RecyclageMotif;
use App\Domain\Recyclage\Events\RecyclageEntryRecorded;
use App\Domain\Recyclage\Models\RecyclageEntry;
use App\Domain\Tenancy\Models\Structure;
use App\Listeners\RecordRecyclageEntryInLedger;
use App\Support\TenantContext;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    TenantContext::set($this->structure);
});

afterEach(fn () => TenantContext::clear());

it('creates an income ledger entry when a recyclage entry is recorded', function () {
    $entry = RecyclageEntry::factory()->create([
        'structure_id' => $this->structure->id,
        'full_name' => 'Awa Tenant',
        'motif' => RecyclageMotif::Test,
        'session_date' => '2026-07-21',
        'amount' => 20000,
    ]);

    (new RecordRecyclageEntryInLedger(app(LedgerService::class)))
        ->handle(new RecyclageEntryRecorded($entry, 20000.0, 'Awa Tenant', '2026-07-21'));

    $ledgerEntry = LedgerEntry::query()->sole();
    expect($ledgerEntry->type->value)->toBe('income');
    expect((float) $ledgerEntry->amount)->toBe(20000.0);
    expect($ledgerEntry->occurred_on->toDateString())->toBe('2026-07-21');
    expect($ledgerEntry->memo)->toContain('Awa Tenant');
});
