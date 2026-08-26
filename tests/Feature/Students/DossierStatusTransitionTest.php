<?php

use App\Domain\Students\Enums\DossierStatus;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\DossierStatusService;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');

    $this->student = Student::factory()->create(['structure_id' => $this->structure->id]);
});

it('lets an admin advance a student\'s dossier through a valid transition', function () {
    expect($this->student->dossier_status)->toBe(DossierStatus::Incomplete);

    $this->actingAs($this->admin)
        ->patch(route('students.dossier-status', $this->student), ['dossier_status' => DossierStatus::Complete->value])
        ->assertRedirect(route('students.show', $this->student));

    expect($this->student->fresh()->dossier_status)->toBe(DossierStatus::Complete);
});

it('rejects an invalid dossier transition with a clear error, not a 500', function () {
    $this->actingAs($this->admin)
        ->patch(route('students.dossier-status', $this->student), ['dossier_status' => DossierStatus::Validated->value])
        ->assertSessionHasErrors('dossier_status');

    expect($this->student->fresh()->dossier_status)->toBe(DossierStatus::Incomplete);
});

it('rejects a dossier_status value that is not a real enum case', function () {
    $this->actingAs($this->admin)
        ->patch(route('students.dossier-status', $this->student), ['dossier_status' => 'not-a-real-status'])
        ->assertSessionHasErrors('dossier_status');
});

it('does not let a moniteur advance a dossier status', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)
        ->patch(route('students.dossier-status', $this->student), ['dossier_status' => DossierStatus::Complete->value])
        ->assertForbidden();

    expect($this->student->fresh()->dossier_status)->toBe(DossierStatus::Incomplete);
});

it('does not let an admin of another tenant advance a dossier status', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $otherAdmin = User::factory()->create(['structure_id' => $otherStructure->id]);
    $otherAdmin->assignRole('admin');

    $this->actingAs($otherAdmin)
        ->patch(route('students.dossier-status', $this->student), ['dossier_status' => DossierStatus::Complete->value])
        ->assertNotFound();

    expect($this->student->fresh()->dossier_status)->toBe(DossierStatus::Incomplete);
});

it('shows the current dossier status and the allowed next transitions on the student profile', function () {
    (new DossierStatusService)->transitionTo($this->student, DossierStatus::Complete);

    $this->actingAs($this->admin)->get(route('students.show', $this->student))
        ->assertOk()
        ->assertSee('Complet')
        ->assertSee('Soumis');
});
