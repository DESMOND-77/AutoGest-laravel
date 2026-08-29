<?php

use App\Domain\Finance\Models\Payment;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Services\OrderService;
use App\Domain\Store\Services\StoreReportService;
use App\Domain\Tenancy\Models\Structure;
use App\Support\TenantContext;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    TenantContext::set($this->structure);
});

afterEach(fn () => TenantContext::clear());

it('reports revenue and top products from real orders', function () {
    $productA = Product::factory()->create(['structure_id' => $this->structure->id, 'name' => 'Livre du code', 'price' => 5000, 'stock_quantity' => 20]);
    $productB = Product::factory()->create(['structure_id' => $this->structure->id, 'name' => 'Gilet', 'price' => 2000, 'stock_quantity' => 20]);

    app(OrderService::class)->place([['product_id' => $productA->id, 'quantity' => 2]], null, 'Client A');
    app(OrderService::class)->place([['product_id' => $productB->id, 'quantity' => 1]], null, 'Client B');

    $report = app(StoreReportService::class)->dashboard();

    expect($report['salesCount'])->toBe(2);
    expect((float) $report['revenueToday'])->toBe(12000.0);
    expect($report['topProducts']->first()['name'])->toBe('Livre du code');
});

it('flags products under their reorder threshold as critical stock', function () {
    Product::factory()->create(['structure_id' => $this->structure->id, 'name' => 'Stock bas', 'stock_quantity' => 1, 'reorder_threshold' => 5]);
    Product::factory()->create(['structure_id' => $this->structure->id, 'name' => 'Stock ok', 'stock_quantity' => 10, 'reorder_threshold' => 5]);

    $report = app(StoreReportService::class)->dashboard();

    expect($report['criticalStock']->pluck('name')->all())->toBe(['Stock bas']);
});

it('excludes cancelled orders from revenue, sales count and top products', function () {
    $kept = Product::factory()->create(['structure_id' => $this->structure->id, 'name' => 'Gilet', 'price' => 2000, 'stock_quantity' => 20]);
    $cancelledProduct = Product::factory()->create(['structure_id' => $this->structure->id, 'name' => 'Triangle', 'price' => 50000, 'stock_quantity' => 20]);

    app(OrderService::class)->place([['product_id' => $kept->id, 'quantity' => 1]], null, 'Client A');
    $cancelled = app(OrderService::class)->place([['product_id' => $cancelledProduct->id, 'quantity' => 2]], null, 'Client B')['order'];

    app(OrderService::class)->cancel($cancelled);

    $report = app(StoreReportService::class)->dashboard();

    expect($report['salesCount'])->toBe(1);
    expect((float) $report['revenueToday'])->toBe(2000.0);
    expect($report['topProducts']->pluck('name')->all())->toBe(['Gilet']);
    // Only the live order's unpaid invoice counts - the cancelled order's
    // 100 000 FCFA is out of the pending balance for good.
    expect((float) $report['pendingBalance'])->toBe(2000.0);
});

it('excludes a cancelled order whose invoice was retained from the pending balance', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'name' => 'Casque', 'price' => 10000, 'stock_quantity' => 20]);
    $order = app(OrderService::class)->place([['product_id' => $product->id, 'quantity' => 1]], null, 'Client')['order'];

    // A partial payment means cancel() keeps the invoice (financial trail).
    Payment::factory()->create([
        'structure_id' => $this->structure->id,
        'invoice_id' => $order->invoice_id,
        'amount' => 1000,
    ]);

    app(OrderService::class)->cancel($order);

    expect((float) app(StoreReportService::class)->dashboard()['pendingBalance'])->toBe(0.0);
});

it('scopes top products to the current tenant only', function () {
    $otherStructure = Structure::factory()->create();

    $ownProduct = Product::factory()->create(['structure_id' => $this->structure->id, 'name' => 'Casque moto', 'price' => 3000, 'stock_quantity' => 20]);
    app(OrderService::class)->place([['product_id' => $ownProduct->id, 'quantity' => 4]], null, 'Client A');

    TenantContext::set($otherStructure);
    $otherProduct = Product::factory()->create(['structure_id' => $otherStructure->id, 'name' => 'Autre produit', 'price' => 9000, 'stock_quantity' => 20]);
    app(OrderService::class)->place([['product_id' => $otherProduct->id, 'quantity' => 10]], null, 'Client B');

    TenantContext::set($this->structure);
    $report = app(StoreReportService::class)->dashboard();

    expect($report['topProducts']->pluck('name')->all())->toBe(['Casque moto']);
    expect($report['topProducts']->first()['quantity'])->toBe(4);
    expect($report['topProducts']->first()['revenue'])->toBe(12000.0);
});
