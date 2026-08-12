<?php

use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

it('creates a pending structure and its admin without logging in', function () {
    $response = $this->post('/register', [
        'school_name' => 'Auto-École Poirier',
        'admin_name' => 'Jeanne Poirier',
        'admin_email' => 'jeanne@poirier-autoecole.ga',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('login'));
    $this->assertGuest();

    $structure = Structure::where('name', 'Auto-École Poirier')->firstOrFail();
    expect($structure->status)->toBe(StructureStatus::Pending);

    $admin = User::where('email', 'jeanne@poirier-autoecole.ga')->firstOrFail();
    expect($admin->structure_id)->toBe($structure->id);
    expect($admin->hasRole('admin'))->toBeTrue();
});

it('allows two different schools to register admins with the same email', function () {
    $this->post('/register', [
        'school_name' => 'École A',
        'admin_name' => 'Admin A',
        'admin_email' => 'shared@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect(route('login'));

    $this->post('/register', [
        'school_name' => 'École B',
        'admin_name' => 'Admin B',
        'admin_email' => 'shared@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect(route('login'));

    expect(User::where('email', 'shared@example.com')->count())->toBe(2);
});

it('rate limits repeated registration attempts', function () {
    for ($i = 0; $i < 6; $i++) {
        $this->post('/register', [
            'school_name' => "École $i",
            'admin_name' => "Admin $i",
            'admin_email' => "admin{$i}@example.com",
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
    }

    $this->post('/register', [
        'school_name' => 'École trop',
        'admin_name' => 'Admin trop',
        'admin_email' => 'trop@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(429);
});
