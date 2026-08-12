<?php

use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * UX-04: an empty list previously just said "Aucun élève." with no
 * explanation or next step. Locks in the explicit empty-state message + CTA
 * from docs/audit/ux-audit.md.
 */
it('shows an explanatory empty state with a call to action when there are no students', function () {
    $this->seed(RoleSeeder::class);

    $structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $admin = User::factory()->create(['structure_id' => $structure->id]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get(route('students.index'));

    $response->assertOk();
    $response->assertSee('Aucun élève trouvé.');
    $response->assertSee(route('students.create'), false);
});
