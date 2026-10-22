<?php

use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

it('shows the custom 404 page for an unknown route', function () {
    $response = $this->get('/this-route-does-not-exist');

    $response->assertNotFound();
    $response->assertSee('Erreur 404');
    $response->assertSee('Page introuvable');
});

it('shows the custom 403 page when a role check fails', function () {
    $this->seed(RoleSeeder::class);

    $structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $admin = User::factory()->create(['structure_id' => $structure->id]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('superadmin.structures.index'));

    $response->assertForbidden();
    $response->assertSee('Erreur 403');
    $response->assertSee('Accès refusé');
});
