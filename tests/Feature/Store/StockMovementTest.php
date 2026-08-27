<?php

use App\Domain\Store\Enums\StockMovementType;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Models\StockMovement;
use App\Domain\Store\Services\OrderService;
use App\Domain\Tenancy\Models\Structure;
use App\Support\TenantContext;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    TenantContext::set($this->structure);
});

afterEach(fn () => TenantContext::clear());

it('logs a negative stock movement for each order line', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'stock_quantity' => 10]);

    $result = app(OrderService::class)->place([['product_id' => $product->id, 'quantity' => 3]], null, 'Client');

    $movement = StockMovement::query()->where('product_id', $product->id)->sole();
    expect($movement->type)->toBe(StockMovementType::Sale);
    expect($movement->quantity)->toBe(-3);
    expect($movement->reference)->toContain((string) $result['order']->id);
});

it('logs a single aggregated stock movement when a product appears in multiple order lines', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'stock_quantity' => 10]);

    $result = app(OrderService::class)->place(
        [
            ['product_id' => $product->id, 'quantity' => 3],
            ['product_id' => $product->id, 'quantity' => 2],
        ],
        null,
        'Client',
    );

    $movement = StockMovement::query()->where('product_id', $product->id)->sole();
    expect($movement->type)->toBe(StockMovementType::Sale);
    expect($movement->quantity)->toBe(-5);
    expect($movement->reference)->toContain((string) $result['order']->id);
});

it('supports a manual stock adjustment', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'stock_quantity' => 5]);

    StockMovement::query()->create([
        'product_id' => $product->id,
        'type' => StockMovementType::Adjustment,
        'quantity' => 2,
        'reference' => 'Inventaire du 27/08',
        'occurred_at' => now(),
    ]);

    expect(StockMovement::query()->count())->toBe(1);
});
