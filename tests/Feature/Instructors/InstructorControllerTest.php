<?php

use App\Domain\Instructors\Models\Instructor;
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

it('lets an admin create an instructor profile for a moniteur user', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($this->admin)
        ->post(route('instructors.store'), [
            'user_id' => $moniteur->id,
            'license_number' => 'MON-0001',
            'hire_date' => '2024-01-15',
        ])
        ->assertRedirect(route('instructors.index'));

    expect(Instructor::query()->where('user_id', $moniteur->id)->exists())->toBeTrue();
});

it('lets an admin list instructors for their own school', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    Instructor::factory()->create(['structure_id' => $this->structure->id, 'user_id' => $moniteur->id]);

    $this->actingAs($this->admin)
        ->get(route('instructors.index'))
        ->assertOk();
});
