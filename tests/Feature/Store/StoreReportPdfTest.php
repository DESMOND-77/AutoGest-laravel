<?php

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

it('lets an admin download the store report as a pdf', function () {
    TenantContext::set($this->structure);
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'price' => 1000, 'stock_quantity' => 5]);
    app(OrderService::class)->place([['product_id' => $product->id, 'quantity' => 1]], null, 'Client');

    $response = $this->actingAs($this->admin)->get(route('store.reports.pdf'));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

it('denies a moniteur access to the pdf export', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->get(route('store.reports.pdf'))->assertForbidden();
});

it('denies a moniteur access to the top-products csv export', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->get(route('store.reports.top-products.csv'))->assertForbidden();
});
