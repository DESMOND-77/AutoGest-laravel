<?php

use App\Domain\Scheduling\Enums\PresenceStatus;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * MT-05 follow-up: Scheduling/LessonSession had no dedicated tenant isolation
 * coverage despite using BelongsToTenant, unlike Students/Invoice/Vehicle.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->schoolA = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->schoolB = Structure::factory()->create(['status' => StructureStatus::Active]);

    $this->adminA = User::factory()->create(['structure_id' => $this->schoolA->id]);
    $this->adminA->assignRole('admin');

    $this->adminB = User::factory()->create(['structure_id' => $this->schoolB->id]);
    $this->adminB->assignRole('admin');

    $this->moniteurA = User::factory()->create(['structure_id' => $this->schoolA->id]);
    $this->moniteurA->assignRole('moniteur');

    $this->moniteurB = User::factory()->create(['structure_id' => $this->schoolB->id]);
    $this->moniteurB->assignRole('moniteur');

    $this->sessionA = LessonSession::factory()->create([
        'structure_id' => $this->schoolA->id,
        'student_id' => Student::factory()->create(['structure_id' => $this->schoolA->id]),
        'instructor_id' => $this->moniteurA->id,
    ]);
});

it('does not let an admin of school B cancel a lesson session belonging to school A', function () {
    $this->actingAs($this->adminB)
        ->delete(route('scheduling.destroy', $this->sessionA))
        ->assertNotFound();

    expect(LessonSession::withoutGlobalScopes()->find($this->sessionA->id)->presence)
        ->not->toBe(PresenceStatus::Cancelled);
});

it('does not let an admin of school B mark presence on a lesson session belonging to school A', function () {
    $this->actingAs($this->adminB)
        ->patch(route('scheduling.presence', $this->sessionA), ['presence' => 'present'])
        ->assertNotFound();
});

it('scopes the weekly planning index to the current tenant', function () {
    $ownSession = LessonSession::factory()->create([
        'structure_id' => $this->schoolA->id,
        'student_id' => Student::factory()->create(['structure_id' => $this->schoolA->id]),
        'instructor_id' => $this->moniteurA->id,
        'scheduled_date' => $this->sessionA->scheduled_date,
    ]);

    $response = $this->actingAs($this->adminA)->get(route('scheduling.index', ['week' => $this->sessionA->scheduled_date]));

    $response->assertOk();
    $sessionIds = $response->viewData('sessions')->pluck('id');

    expect($sessionIds)->toContain($this->sessionA->id, $ownSession->id);

    $foreignSession = LessonSession::factory()->create([
        'structure_id' => $this->schoolB->id,
        'student_id' => Student::factory()->create(['structure_id' => $this->schoolB->id]),
        'instructor_id' => $this->moniteurB->id,
        'scheduled_date' => $this->sessionA->scheduled_date,
    ]);

    expect($sessionIds)->not->toContain($foreignSession->id);
});
