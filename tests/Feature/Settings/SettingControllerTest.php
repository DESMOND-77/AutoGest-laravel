<?php

use App\Domain\Settings\Models\Setting;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->schoolA = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->schoolB = Structure::factory()->create(['status' => StructureStatus::Active]);

    $this->adminA = User::factory()->create(['structure_id' => $this->schoolA->id]);
    $this->adminA->assignRole('admin');

    $this->adminB = User::factory()->create(['structure_id' => $this->schoolB->id]);
    $this->adminB->assignRole('admin');
});

it('creates the settings row for a tenant on first visit', function () {
    $this->actingAs($this->adminA)
        ->get(route('settings.show'))
        ->assertOk();

    expect(Setting::query()->where('structure_id', $this->schoolA->id)->exists())->toBeTrue();
});

it('lets an admin update their own school settings', function () {
    $this->actingAs($this->adminA)
        ->patch(route('settings.update'), [
            'display_name' => 'Auto-École J/H',
            'currency' => 'FCFA',
        ])
        ->assertRedirect(route('settings.show'));

    expect(Setting::query()->where('structure_id', $this->schoolA->id)->first()->display_name)
        ->toBe('Auto-École J/H');
});

it('does not let an admin see or edit settings belonging to a different school through a stale row', function () {
    $settingA = Setting::factory()->create(['structure_id' => $this->schoolA->id]);

    $this->actingAs($this->adminB)->get(route('settings.show'))->assertOk();

    expect(Setting::withoutGlobalScopes()->find($settingA->id)->structure_id)->toBe($this->schoolA->id);
    expect(Setting::query()->where('structure_id', $this->schoolB->id)->exists())->toBeTrue();
});
