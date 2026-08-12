<?php

use App\Domain\Store\Models\Supplier;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * TECH-02: SupplierController used to authorize against Product::class as a
 * stand-in for a missing SupplierPolicy. Now it has its own.
 */
beforeEach(fn () => $this->seed(RoleSeeder::class));

it('lets an admin create a supplier', function () {
    $structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $admin = User::factory()->create(['structure_id' => $structure->id]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->post(route('store.suppliers.store'), ['name' => 'Pièces Auto Libreville'])
        ->assertRedirect();

    $supplier = Supplier::query()->sole();
    expect($supplier->name)->toBe('Pièces Auto Libreville');
    expect($supplier->structure_id)->toBe($structure->id);
});

it('does not let a moniteur create a supplier', function () {
    $structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $moniteur = User::factory()->create(['structure_id' => $structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)
        ->post(route('store.suppliers.store'), ['name' => 'Pièces Auto Libreville'])
        ->assertForbidden();

    expect(Supplier::query()->count())->toBe(0);
});
