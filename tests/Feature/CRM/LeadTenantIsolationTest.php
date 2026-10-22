<?php

use App\Domain\CRM\Enums\LeadStatus;
use App\Domain\CRM\Models\Lead;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * MT-05 follow-up: CRM/Lead had only a conversion test, no tenant isolation
 * coverage, despite using BelongsToTenant like every other domain model.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->schoolA = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->schoolB = Structure::factory()->create(['status' => StructureStatus::Active]);

    $this->adminA = User::factory()->create(['structure_id' => $this->schoolA->id]);
    $this->adminA->assignRole('admin');

    $this->adminB = User::factory()->create(['structure_id' => $this->schoolB->id]);
    $this->adminB->assignRole('admin');

    $this->leadA = Lead::factory()->create(['structure_id' => $this->schoolA->id, 'name' => 'Prospect A']);
});

it('does not let an admin of school B update the status of a lead belonging to school A', function () {
    $this->actingAs($this->adminB)
        ->patch(route('crm.leads.status', $this->leadA), ['status' => 'contacted'])
        ->assertNotFound();

    expect($this->leadA->fresh()->status)->toBe(LeadStatus::New);
});

it('does not let an admin of school B convert a lead belonging to school A', function () {
    $this->actingAs($this->adminB)
        ->post(route('crm.leads.convert', $this->leadA))
        ->assertNotFound();
});

it('scopes the leads index to the current tenant', function () {
    Lead::factory()->create(['structure_id' => $this->schoolB->id, 'name' => 'Prospect B']);

    $response = $this->actingAs($this->adminA)->get(route('crm.leads.index'));

    $response->assertOk();
    $response->assertSee('Prospect A');
    $response->assertDontSee('Prospect B');
});
