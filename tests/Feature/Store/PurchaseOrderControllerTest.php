<?php

use App\Domain\Store\Enums\PurchaseOrderStatus;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Models\PurchaseOrder;
use App\Domain\Store\Models\Supplier;
use App\Domain\Store\Services\PurchaseOrderService;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    TenantContext::set($this->structure);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
    $this->supplier = Supplier::factory()->create(['structure_id' => $this->structure->id]);
    $this->product = Product::factory()->create(['structure_id' => $this->structure->id, 'stock_quantity' => 0]);
});

afterEach(fn () => TenantContext::clear());

it('lets an admin place a purchase order', function () {
    $this->actingAs($this->admin)->post(route('store.purchase-orders.store'), [
        'supplier_id' => $this->supplier->id,
        'items' => [['product_id' => $this->product->id, 'quantity' => 15]],
    ])->assertRedirect(route('store.purchase-orders.index'));

    expect(PurchaseOrder::query()->where('supplier_id', $this->supplier->id)->exists())->toBeTrue();
});

it('lets an admin receive a purchase order and updates stock', function () {
    $order = app(PurchaseOrderService::class)
        ->place($this->supplier, [['product_id' => $this->product->id, 'quantity' => 15]]);

    $this->actingAs($this->admin)->post(route('store.purchase-orders.receive', $order), [
        'received' => [$this->product->id => 15],
    ])->assertRedirect(route('store.purchase-orders.index'));

    expect($this->product->fresh()->stock_quantity)->toBe(15);
    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Received);
});

it('denies a moniteur access to purchase order routes', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->get(route('store.purchase-orders.index'))->assertForbidden();
});

it('scopes the purchase-order index to the current tenant', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $otherSupplier = Supplier::factory()->create(['structure_id' => $otherStructure->id, 'name' => 'Autre Fournisseur']);
    TenantContext::set($otherStructure);
    app(PurchaseOrderService::class)->place($otherSupplier, []);
    TenantContext::set($this->structure);

    $this->actingAs($this->admin)->get(route('store.purchase-orders.index'))
        ->assertOk()
        ->assertDontSee('Autre Fournisseur');
});
