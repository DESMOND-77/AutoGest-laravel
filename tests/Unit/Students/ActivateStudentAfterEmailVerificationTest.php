<?php

use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Events\StudentEmailVerified;
use App\Domain\Students\Listeners\ActivateStudentAfterEmailVerification;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\LifecycleService;
use App\Domain\Tenancy\Models\Structure;

it('chains prospect straight through pre-enrollment to dossier setup', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);

    expect($student->lifecycle_stage)->toBe(LifecycleStage::Prospect);

    (new ActivateStudentAfterEmailVerification(new LifecycleService))
        ->handle(new StudentEmailVerified($student));

    expect($student->fresh()->lifecycle_stage)->toBe(LifecycleStage::DossierSetup);
});
