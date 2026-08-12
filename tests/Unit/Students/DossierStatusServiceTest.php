<?php

use App\Domain\Students\Enums\DossierStatus;
use App\Domain\Students\Exceptions\InvalidDossierTransition;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\DossierStatusService;
use App\Domain\Tenancy\Models\Structure;

it('advances a dossier through an allowed transition', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);

    (new DossierStatusService)->transitionTo($student, DossierStatus::Complete);

    expect($student->fresh()->dossier_status)->toBe(DossierStatus::Complete);
});

it('rejects a transition that skips stages', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);

    expect(fn () => (new DossierStatusService)->transitionTo($student, DossierStatus::Validated))
        ->toThrow(InvalidDossierTransition::class);
});

it('allows a submitted dossier to be sent back as incomplete', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);

    $service = new DossierStatusService;
    $service->transitionTo($student, DossierStatus::Complete);
    $service->transitionTo($student, DossierStatus::Submitted);
    $service->transitionTo($student, DossierStatus::Incomplete);

    expect($student->fresh()->dossier_status)->toBe(DossierStatus::Incomplete);
});

it('does not allow a validated dossier to transition further', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);

    $service = new DossierStatusService;
    $service->transitionTo($student, DossierStatus::Complete);
    $service->transitionTo($student, DossierStatus::Submitted);
    $service->transitionTo($student, DossierStatus::Validated);

    expect(fn () => $service->transitionTo($student, DossierStatus::Incomplete))
        ->toThrow(InvalidDossierTransition::class);
});
