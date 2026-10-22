<?php

use App\Domain\Store\Models\Order;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * MT-05 follow-up: Store/Order had no tenant isolation coverage. There is no
 * per-order show/update route today (only index+store), so the isolation
 * surface is the index listing - this locks that down against a future
 * per-order route being added without tenant scoping in mind.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->schoolA = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->schoolB = Structure::factory()->create(['status' => StructureStatus::Active]);

    $this->adminA = User::factory()->create(['structure_id' => $this->schoolA->id]);
    $this->adminA->assignRole('admin');
});

it('scopes the orders index to the current tenant', function () {
    $orderA = Order::factory()->create(['structure_id' => $this->schoolA->id, 'total' => 15000]);
    $orderB = Order::factory()->create(['structure_id' => $this->schoolB->id, 'total' => 99000]);

    $response = $this->actingAs($this->adminA)->get(route('store.orders.index'));

    $response->assertOk();
    $orderIds = $response->viewData('orders')->pluck('id');

    expect($orderIds)->toContain($orderA->id);
    expect($orderIds)->not->toContain($orderB->id);
});
