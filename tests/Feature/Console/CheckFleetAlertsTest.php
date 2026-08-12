<?php

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Notifications\Notifications\AlertNotification;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

/**
 * MT-02 follow-up: fleet:check-alerts loops every Structure, binding
 * TenantContext to each in turn. The loop body now runs inside a
 * try/finally so TenantContext::clear() always runs before moving to the
 * next structure, even if something inside throws — this test locks down
 * that each structure's admins only ever see their own vehicles' alerts.
 */
it('only notifies each structure\'s admins about their own expiring vehicles', function () {
    Notification::fake();
    $this->seed(RoleSeeder::class);

    $schoolA = Structure::factory()->create(['status' => StructureStatus::Active]);
    $schoolB = Structure::factory()->create(['status' => StructureStatus::Active]);

    $adminA = User::factory()->create(['structure_id' => $schoolA->id]);
    $adminA->assignRole('admin');

    $adminB = User::factory()->create(['structure_id' => $schoolB->id]);
    $adminB->assignRole('admin');

    Vehicle::factory()->create([
        'structure_id' => $schoolA->id,
        'technical_inspection_expires_at' => now()->addDays(10),
    ]);

    Vehicle::factory()->create([
        'structure_id' => $schoolB->id,
        'technical_inspection_expires_at' => null,
        'insurance_expires_at' => null,
    ]);

    $this->artisan('fleet:check-alerts')->assertSuccessful();

    Notification::assertSentTo($adminA, AlertNotification::class);
    Notification::assertNotSentTo($adminB, AlertNotification::class);
});
