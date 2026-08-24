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

it('lets an admin manage a required document type belonging to their own tenant', function () {
    $type = RequiredDocumentType::factory()->create(['structure_id' => $this->structure->id]);

    expect($this->admin->can('viewAny', RequiredDocumentType::class))->toBeTrue();
    expect($this->admin->can('create', RequiredDocumentType::class))->toBeTrue();
    expect($this->admin->can('update', $type))->toBeTrue();
});

it('denies an admin from updating another tenant\'s required document type', function () {
    $otherStructure = Structure::factory()->create();
    $type = RequiredDocumentType::factory()->create(['structure_id' => $otherStructure->id]);

    expect($this->admin->can('update', $type))->toBeFalse();
});

it('denies a non-admin role entirely', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    expect($moniteur->can('viewAny', RequiredDocumentType::class))->toBeFalse();
});
