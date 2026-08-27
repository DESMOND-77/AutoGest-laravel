<?php

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
