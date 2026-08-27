<?php

use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Store\Enums\OrderStatus;
use App\Domain\Store\Enums\StockMovementType;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Models\StockMovement;
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

it('logs an adjustment stock movement when an order is cancelled', function () {
    TenantContext::set($this->structure);
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'price' => 2000, 'stock_quantity' => 10]);
    $order = app(OrderService::class)->place([['product_id' => $product->id, 'quantity' => 3]], null, 'Client')['order'];

    app(OrderService::class)->cancel($order);

    $movement = StockMovement::query()
        ->where('product_id', $product->id)
        ->where('type', StockMovementType::Adjustment)
        ->first();

    expect($movement)->not->toBeNull();
    expect($movement->quantity)->toBe(3);
    expect($movement->reference)->toBe("Annulation commande #{$order->id}");
});

it('is idempotent: cancelling twice restores stock only once', function () {
    TenantContext::set($this->structure);
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'price' => 2000, 'stock_quantity' => 10]);
    $order = app(OrderService::class)->place([['product_id' => $product->id, 'quantity' => 3]], null, 'Client')['order'];

    app(OrderService::class)->cancel($order);
    expect($product->fresh()->stock_quantity)->toBe(10);

    app(OrderService::class)->cancel($order->fresh());

    expect($product->fresh()->stock_quantity)->toBe(10);
    expect(StockMovement::query()->where('product_id', $product->id)->where('type', StockMovementType::Adjustment)->count())->toBe(1);
});

it('denies cancelling an order that is already cancelled', function () {
    TenantContext::set($this->structure);
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'price' => 2000, 'stock_quantity' => 10]);
    $order = app(OrderService::class)->place([['product_id' => $product->id, 'quantity' => 3]], null, 'Client')['order'];

    $this->actingAs($this->admin)->post(route('store.orders.cancel', $order))->assertRedirect();
    $this->actingAs($this->admin)->post(route('store.orders.cancel', $order->fresh()))->assertForbidden();

    expect($product->fresh()->stock_quantity)->toBe(10);
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
