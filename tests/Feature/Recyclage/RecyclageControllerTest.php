<?php

use App\Domain\Finance\Models\LedgerEntry;
use App\Domain\Recyclage\Enums\RecyclageMotif;
use App\Domain\Recyclage\Models\RecyclageEntry;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
});

it('lets an admin record a recyclage entry that immediately posts to the ledger', function () {
    $instructor = User::factory()->create(['structure_id' => $this->structure->id]);
    $instructor->assignRole('moniteur');

    $response = $this->actingAs($this->admin)->post(route('recyclage.store'), [
        'full_name' => 'Jean Mabika',
        'motif' => RecyclageMotif::Recyclage->value,
        'phone' => '074000000',
        'instructor_id' => $instructor->id,
        'session_date' => now()->toDateString(),
        'amount' => 15000,
    ]);

    $response->assertRedirect(route('recyclage.index'));

    $entry = RecyclageEntry::query()->where('full_name', 'Jean Mabika')->sole();
    expect($entry->structure_id)->toBe($this->structure->id);

    $ledgerEntry = LedgerEntry::query()->sole();
    expect((float) $ledgerEntry->amount)->toBe(15000.0);
    expect($ledgerEntry->type->value)->toBe('income');
});

it('validates the required fields', function () {
    $this->actingAs($this->admin)->post(route('recyclage.store'), [])
        ->assertSessionHasErrors(['full_name', 'motif', 'session_date', 'amount']);

    expect(RecyclageEntry::query()->count())->toBe(0);
});

it('scopes the index list to the current tenant', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    RecyclageEntry::factory()->create(['structure_id' => $this->structure->id, 'full_name' => 'Awa Tenant']);
    RecyclageEntry::factory()->create(['structure_id' => $otherStructure->id, 'full_name' => 'Autre Ecole']);

    $this->actingAs($this->admin)->get(route('recyclage.index'))
        ->assertOk()
        ->assertSee('Awa Tenant')
        ->assertDontSee('Autre Ecole');
});

it('denies a moniteur access to recyclage routes entirely', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->get(route('recyclage.index'))->assertForbidden();
    $this->actingAs($moniteur)->post(route('recyclage.store'), [])->assertForbidden();
});

it('denies an eleve access to recyclage routes entirely', function () {
    $eleve = User::factory()->create(['structure_id' => $this->structure->id]);
    $eleve->assignRole('eleve');

    $this->actingAs($eleve)->get(route('recyclage.index'))->assertForbidden();
});

it('lets an admin delete a recyclage entry', function () {
    $entry = RecyclageEntry::factory()->create(['structure_id' => $this->structure->id]);

    $this->actingAs($this->admin)->delete(route('recyclage.destroy', $entry))
        ->assertRedirect(route('recyclage.index'));

    expect(RecyclageEntry::query()->count())->toBe(0);
});

it('does not let an admin delete another tenant\'s recyclage entry', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $entry = RecyclageEntry::factory()->create(['structure_id' => $otherStructure->id]);

    $this->actingAs($this->admin)->delete(route('recyclage.destroy', $entry))
        ->assertNotFound();

    expect(RecyclageEntry::withoutGlobalScopes()->find($entry->id))->not->toBeNull();
});
