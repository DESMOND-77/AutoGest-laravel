<?php

use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
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

it('denies a moniteur access to the boutique screen', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->get(route('store.index'))->assertForbidden();
});
