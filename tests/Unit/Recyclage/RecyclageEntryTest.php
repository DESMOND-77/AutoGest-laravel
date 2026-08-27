<?php

use App\Domain\Recyclage\Enums\RecyclageMotif;
use App\Domain\Recyclage\Models\RecyclageEntry;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use App\Support\TenantContext;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    TenantContext::set($this->structure);
});

afterEach(fn () => TenantContext::clear());

it('creates a recyclage entry scoped to the current tenant', function () {
    $instructor = User::factory()->create(['structure_id' => $this->structure->id]);

    $entry = RecyclageEntry::query()->create([
        'full_name' => 'Jean Mabika',
        'motif' => RecyclageMotif::Recyclage->value,
        'phone' => '074000000',
        'instructor_id' => $instructor->id,
        'session_date' => now()->toDateString(),
        'amount' => 15000,
    ]);

    expect($entry->structure_id)->toBe($this->structure->id);
    expect($entry->motif)->toBe(RecyclageMotif::Recyclage);
    expect($entry->fresh()->amount)->toBe('15000.00');
    expect($entry->instructor->id)->toBe($instructor->id);
});

it('scopes queries to the current tenant only', function () {
    $otherStructure = Structure::factory()->create();

    RecyclageEntry::factory()->create(['structure_id' => $this->structure->id]);
    RecyclageEntry::factory()->create(['structure_id' => $otherStructure->id]);

    expect(RecyclageEntry::query()->count())->toBe(1);
});
