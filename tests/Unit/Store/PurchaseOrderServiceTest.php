<?php

use App\Domain\Store\Enums\PurchaseOrderStatus;
use App\Domain\Store\Enums\StockMovementType;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Models\StockMovement;
use App\Domain\Store\Models\Supplier;
use App\Domain\Store\Services\PurchaseOrderService;
use App\Domain\Tenancy\Models\Structure;
use App\Support\TenantContext;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    TenantContext::set($this->structure);
    $this->supplier = Supplier::factory()->create(['structure_id' => $this->structure->id]);
    $this->service = new PurchaseOrderService;
});

afterEach(fn () => TenantContext::clear());

it('creates a pending purchase order with its line items', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id]);

    $order = $this->service->place($this->supplier, [['product_id' => $product->id, 'quantity' => 20]]);

    expect($order->status)->toBe(PurchaseOrderStatus::Pending);
    expect($order->items()->sole()->quantity)->toBe(20);
});

it('increments stock and logs a reception movement when fully received', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'stock_quantity' => 5]);
    $order = $this->service->place($this->supplier, [['product_id' => $product->id, 'quantity' => 20]]);

    $this->service->receive($order, [$product->id => 20]);

    expect($product->fresh()->stock_quantity)->toBe(25);
    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::Received);
    $movement = StockMovement::query()->where('product_id', $product->id)->sole();
    expect($movement->type)->toBe(StockMovementType::Reception);
    expect($movement->quantity)->toBe(20);
});

it('marks the order partially received when quantities fall short', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'stock_quantity' => 0]);
    $order = $this->service->place($this->supplier, [['product_id' => $product->id, 'quantity' => 20]]);

    $this->service->receive($order, [$product->id => 12]);

    expect($product->fresh()->stock_quantity)->toBe(12);
    expect($order->fresh()->status)->toBe(PurchaseOrderStatus::PartiallyReceived);
});
