<?php

use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
});

it('lets an admin view and manage users in their own tenant', function () {
    $target = User::factory()->create(['structure_id' => $this->structure->id]);

    expect($this->admin->can('viewAny', User::class))->toBeTrue();
    expect($this->admin->can('create', User::class))->toBeTrue();
    expect($this->admin->can('update', $target))->toBeTrue();
});

it('denies an admin from managing another tenant\'s users', function () {
    $otherStructure = Structure::factory()->create();
    $target = User::factory()->create(['structure_id' => $otherStructure->id]);

    expect($this->admin->can('update', $target))->toBeFalse();
});

it('denies a non-admin role entirely', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    expect($moniteur->can('viewAny', User::class))->toBeFalse();
    expect($moniteur->can('create', User::class))->toBeFalse();
});
