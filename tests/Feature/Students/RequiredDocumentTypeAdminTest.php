<?php

use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
});

it('lets an admin add a required document type', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('settings.document-types.store'), ['label' => "Carte d'identité"]);

    $response->assertRedirect();
    expect(RequiredDocumentType::query()->where('label', "Carte d'identité")->where('structure_id', $this->structure->id)->exists())->toBeTrue();
});

it('lets an admin deactivate a required document type without deleting it', function () {
    $type = RequiredDocumentType::factory()->create(['structure_id' => $this->structure->id]);

    $this->actingAs($this->admin)->patch(route('settings.document-types.update', $type), ['is_active' => '0']);

    expect($type->fresh()->is_active)->toBeFalse();
    expect(RequiredDocumentType::query()->whereKey($type->id)->exists())->toBeTrue();
});

it('never lets an admin update another tenant\'s required document type', function () {
    $otherStructure = Structure::factory()->create();
    $type = RequiredDocumentType::factory()->create(['structure_id' => $otherStructure->id]);

    $this->actingAs($this->admin)
        ->patch(route('settings.document-types.update', $type), ['is_active' => '0'])
        ->assertNotFound();

    expect($type->fresh()->is_active)->toBeTrue();
});

it('denies a non-admin role', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->get(route('settings.document-types.index'))->assertForbidden();
});
