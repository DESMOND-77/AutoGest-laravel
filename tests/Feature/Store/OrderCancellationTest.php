<?php

use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Store\Enums\OrderStatus;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Services\OrderService;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
});

afterEach(fn () => TenantContext::clear());

it('restores stock and voids the invoice when an order is cancelled', function () {
    TenantContext::set($this->structure);
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'price' => 2000, 'stock_quantity' => 10]);
    $result = app(OrderService::class)->place([['product_id' => $product->id, 'quantity' => 3]], null, 'Client');
    $order = $result['order'];

    expect($product->fresh()->stock_quantity)->toBe(7);

    $this->actingAs($this->admin)->post(route('store.orders.cancel', $order))
        ->assertRedirect(route('store.orders.index'));

    expect($product->fresh()->stock_quantity)->toBe(10);
    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
    expect(Invoice::query()->find($order->invoice_id))->toBeNull();
});

it('does not let an admin cancel another tenant\'s order', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    TenantContext::set($otherStructure);
    $product = Product::factory()->create(['structure_id' => $otherStructure->id, 'price' => 1000, 'stock_quantity' => 5]);
    $result = app(OrderService::class)->place([['product_id' => $product->id, 'quantity' => 1]], null, 'Client');

    $this->actingAs($this->admin)->post(route('store.orders.cancel', $result['order']))
        ->assertNotFound();
});

it('keeps the invoice when it already has a payment recorded against it, but still restores stock', function () {
    TenantContext::set($this->structure);
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'price' => 2000, 'stock_quantity' => 10]);
    $result = app(OrderService::class)->place([['product_id' => $product->id, 'quantity' => 3]], null, 'Client');
    $order = $result['order'];

    Payment::factory()->create([
        'structure_id' => $this->structure->id,
        'invoice_id' => $order->invoice_id,
        'recorded_by' => $this->admin->id,
        'amount' => 6000,
    ]);

    $this->actingAs($this->admin)->post(route('store.orders.cancel', $order))
        ->assertRedirect(route('store.orders.index'));

    expect($product->fresh()->stock_quantity)->toBe(10);
    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
    expect($order->fresh()->invoice_id)->toBe($result['order']->invoice_id);
    expect(Invoice::query()->find($result['order']->invoice_id))->not->toBeNull();
});
