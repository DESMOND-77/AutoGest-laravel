<?php

use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Events\StudentStageChanged;
use App\Domain\Students\Exceptions\InvalidStageTransition;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\LifecycleService;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Support\Facades\Event;

it('advances a student through an allowed transition and dispatches an event', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);

    // Faked after creation, and scoped to this one event, so Student's own
    // `creating` hook (which sets the default lifecycle_stage) still fires
    // normally — a blanket Event::fake() would silently suppress it too,
    // since Eloquent model events go through the same dispatcher.
    Event::fake(StudentStageChanged::class);

    (new LifecycleService)->transitionTo($student, LifecycleStage::PreEnrollment);

    expect($student->fresh()->lifecycle_stage)->toBe(LifecycleStage::PreEnrollment);

    Event::assertDispatched(StudentStageChanged::class, fn ($event) => $event->student->is($student)
        && $event->from === LifecycleStage::Prospect
        && $event->to === LifecycleStage::PreEnrollment);
});

it('rejects a transition that skips stages', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);

    expect(fn () => (new LifecycleService)->transitionTo($student, LifecycleStage::LicenseObtained))
        ->toThrow(InvalidStageTransition::class);
});

it('allows the retake loop from a failed practical exam back to continuous evaluation', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->stage(LifecycleStage::PracticalExam)->create(['structure_id' => $structure->id]);

    (new LifecycleService)->transitionTo($student, LifecycleStage::ContinuousEvaluation);

    expect($student->fresh()->lifecycle_stage)->toBe(LifecycleStage::ContinuousEvaluation);
});
