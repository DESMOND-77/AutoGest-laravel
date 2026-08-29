<?php

namespace App\Domain\Students\Listeners;

use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Events\StudentEmailVerified;
use App\Domain\Students\Services\LifecycleService;

/**
 * Both transitions fire back-to-back with no visible intermediate state for
 * the student - see the design's "Transitions automatiques vs manuelles"
 * table. Injecting LifecycleService (rather than `new`-ing it, unlike
 * LogStageChange's plain `new LifecycleService` in its own tests) keeps this
 * listener consistent with how every controller in this codebase resolves
 * it, and lets Laravel's container inject it normally when the event fires
 * for real.
 */
class ActivateStudentAfterEmailVerification
{
    public function __construct(
        private readonly LifecycleService $lifecycle,
    ) {}

    public function handle(StudentEmailVerified $event): void
    {
        $this->lifecycle->transitionTo($event->student, LifecycleStage::PreEnrollment);
        $this->lifecycle->transitionTo($event->student, LifecycleStage::DossierSetup);
    }
}
