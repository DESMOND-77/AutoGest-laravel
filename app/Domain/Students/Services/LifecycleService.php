<?php

namespace App\Domain\Students\Services;

use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Events\StudentStageChanged;
use App\Domain\Students\Exceptions\InvalidStageTransition;
use App\Domain\Students\Models\Student;
use Illuminate\Support\Facades\Event;

/**
 * The only place allowed to change a student's lifecycle_stage. Every
 * transition is validated against LifecycleStage::allowedNextStages() and
 * announced via an event, so side effects (documents, notifications, future
 * invoicing triggers) never need to be duplicated at every call site the way
 * the legacy app duplicated its business rules across pages.
 */
class LifecycleService
{
    public function transitionTo(Student $student, LifecycleStage $target): Student
    {
        $current = $student->lifecycle_stage;

        if (! $current->canTransitionTo($target)) {
            throw InvalidStageTransition::from($current, $target);
        }

        $student->lifecycle_stage = $target;
        $student->save();

        Event::dispatch(new StudentStageChanged($student, $current, $target));

        return $student;
    }
}
