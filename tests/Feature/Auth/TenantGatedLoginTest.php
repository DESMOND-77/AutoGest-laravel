<?php

use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('blocks login for a user whose structure is pending validation', function () {
    $structure = Structure::factory()->create(['status' => StructureStatus::Pending]);
    $user = User::factory()->create([
        'structure_id' => $structure->id,
        'password' => Hash::make('password'),
    ]);
    $user->assignRole('admin');

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('blocks login for a user whose structure is suspended', function () {
    $structure = Structure::factory()->create(['status' => StructureStatus::Suspended]);
    $user = User::factory()->create([
        'structure_id' => $structure->id,
        'password' => Hash::make('password'),
    ]);
    $user->assignRole('admin');

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('lets a user of an active structure log in and redirects to their role dashboard', function () {
    $structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $user = User::factory()->create([
        'structure_id' => $structure->id,
        'password' => Hash::make('password'),
    ]);
    $user->assignRole('admin');

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);

    $this->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
});

it('lets the super-admin log in without a tenant', function () {
    $superadmin = User::factory()->create([
        'structure_id' => null,
        'password' => Hash::make('password'),
    ]);
    $superadmin->assignRole('superadmin');

    $this->post('/login', ['email' => $superadmin->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->get(route('dashboard'))->assertRedirect(route('superadmin.structures.index'));
});
