<?php

use App\Domain\Finance\Enums\LedgerEntryType;
use App\Domain\Finance\Models\LedgerEntry;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');

    LedgerEntry::factory()->create([
        'structure_id' => $this->structure->id,
        'type' => LedgerEntryType::Income,
        'amount' => 42000,
        'occurred_on' => now()->toDateString(),
    ]);
});

it('renders the admin dashboard with revenue, exam and fleet stats', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Recettes des 6 derniers mois')
        ->assertSee('42 000 FCFA');
});

it('exports the revenue CSV scoped to the admin\'s own tenant', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    LedgerEntry::factory()->create([
        'structure_id' => $otherStructure->id,
        'type' => LedgerEntryType::Income,
        'amount' => 999999,
        'occurred_on' => now()->toDateString(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('reports.revenue.csv'));

    $response->assertOk();
    $response->assertHeader('content-disposition', 'attachment; filename=recettes-mensuelles.csv');

    $content = $response->streamedContent();
    expect($content)->toContain('42000');
    expect($content)->not->toContain('999999');
});
