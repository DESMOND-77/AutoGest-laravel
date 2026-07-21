<?php

use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Events\StudentStageChanged;
use App\Domain\Students\Exceptions\InvalidStageTransition;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\LifecycleService;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Support\Facades\Event;

it('advances a student through an allowed transition and dispatches an event', function () {
    Event::fake();

    $structure = Structure::factory()->create();
    $student = Student::factory()->create([
        'structure_id' => $structure->id,
        'lifecycle_stage' => LifecycleStage::Prospect,
    ]);

    (new LifecycleService)->transitionTo($student, LifecycleStage::PreEnrollment);

    expect($student->fresh()->lifecycle_stage)->toBe(LifecycleStage::PreEnrollment);

    Event::assertDispatched(StudentStageChanged::class, fn ($event) => $event->student->is($student)
        && $event->from === LifecycleStage::Prospect
        && $event->to === LifecycleStage::PreEnrollment);
});

it('rejects a transition that skips stages', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create([
        'structure_id' => $structure->id,
        'lifecycle_stage' => LifecycleStage::Prospect,
    ]);

    expect(fn () => (new LifecycleService)->transitionTo($student, LifecycleStage::LicenseObtained))
        ->toThrow(InvalidStageTransition::class);
});

it('allows the retake loop from a failed practical exam back to continuous evaluation', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create([
        'structure_id' => $structure->id,
        'lifecycle_stage' => LifecycleStage::PracticalExam,
    ]);

    (new LifecycleService)->transitionTo($student, LifecycleStage::ContinuousEvaluation);

    expect($student->fresh()->lifecycle_stage)->toBe(LifecycleStage::ContinuousEvaluation);
});
