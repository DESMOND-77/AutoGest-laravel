<?php

use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
});

it('refuses login for a deactivated account even with the correct password', function () {
    $user = User::factory()->inactive()->create([
        'structure_id' => $this->structure->id,
        'password' => Hash::make('correct-password'),
    ]);
    $user->assignRole('admin');

    $response = $this->post('/login', ['email' => $user->email, 'password' => 'correct-password']);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('logs out an already-authenticated user the moment their account is deactivated', function () {
    $user = User::factory()->create(['structure_id' => $this->structure->id]);
    $user->assignRole('admin');

    // route('dashboard') redirects admins straight to admin.dashboard (see
    // DashboardController), so hit that role dashboard directly to get a 200
    // from an active session before deactivating the account.
    $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();

    $user->update(['is_active' => false]);

    $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});
