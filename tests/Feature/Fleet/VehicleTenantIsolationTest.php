<?php

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->schoolA = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->schoolB = Structure::factory()->create(['status' => StructureStatus::Active]);

    $this->adminB = User::factory()->create(['structure_id' => $this->schoolB->id]);
    $this->adminB->assignRole('admin');

    $this->vehicleA = Vehicle::factory()->create(['structure_id' => $this->schoolA->id]);
});

it('does not let an admin of school B log maintenance on a vehicle belonging to school A', function () {
    $this->actingAs($this->adminB)
        ->post(route('fleet.maintenance.store', $this->vehicleA), [
            'type' => 'vidange',
            'performed_on' => now()->toDateString(),
        ])
        ->assertForbidden();
});

it('does not let an admin of school B delete a vehicle belonging to school A', function () {
    $this->actingAs($this->adminB)
        ->delete(route('fleet.destroy', $this->vehicleA))
        ->assertForbidden();

    expect(Vehicle::withoutGlobalScopes()->find($this->vehicleA->id))->not->toBeNull();
});
