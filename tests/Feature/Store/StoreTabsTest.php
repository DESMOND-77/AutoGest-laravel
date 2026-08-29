<?php

use App\Domain\Store\Models\Product;
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

it('renders all four boutique tabs in one screen for an admin', function () {
    $this->actingAs($this->admin)->get(route('store.index'))
        ->assertOk()
        ->assertSee('Ventes')
        ->assertSee('Rapports')
        ->assertSee('Produits')
        ->assertSee('Réapprovisionnement', false);
});

it('renders each boutique pane its own content, not just the tab labels', function () {
    $this->actingAs($this->admin)->get(route('store.index'))
        ->assertOk()
        ->assertSee('Nouvelle vente')
        ->assertSee('CA ce mois')
        ->assertSee('Stocks critiques')
        ->assertSee('Nouveau produit')
        ->assertSee('Nouvelle commande fournisseur');
});

it('offers a zero-stock product in the sale form (insufficient stock warns, it does not block)', function () {
    TenantContext::set($this->structure);
    Product::factory()->create(['structure_id' => $this->structure->id, 'name' => 'Rupture totale', 'stock_quantity' => 0, 'active' => true]);
    TenantContext::clear();

    $this->actingAs($this->admin)->get(route('store.index'))
        ->assertOk()
        ->assertSee('Rupture totale');
});

it('accepts cost price, reorder threshold and barcode from the product form', function () {
    $this->actingAs($this->admin)->post(route('store.products.store'), [
        'name' => 'Gilet fluo',
        'price' => 3000,
        'stock_quantity' => 12,
        'cost_price' => 1800,
        'reorder_threshold' => 4,
        'barcode' => '3760001234567',
    ])->assertRedirect();

    TenantContext::set($this->structure);
    $product = Product::query()->where('name', 'Gilet fluo')->firstOrFail();
    TenantContext::clear();

    expect((float) $product->cost_price)->toBe(1800.0);
    expect($product->reorder_threshold)->toBe(4);
    expect($product->barcode)->toBe('3760001234567');
});

it('denies a moniteur access to the boutique screen', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->get(route('store.index'))->assertForbidden();
});
