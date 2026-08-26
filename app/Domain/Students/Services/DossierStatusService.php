<?php

namespace App\Domain\Students\Services;

use App\Domain\Students\Enums\DossierStatus;
use App\Domain\Students\Exceptions\InvalidDossierTransition;
use App\Domain\Students\Models\Student;

/**
 * The only place allowed to change a student's dossier_status once set.
 * Mirrors LifecycleService - see WF-03 in docs/audit/business-workflow.md.
 * No HTTP route uses this yet; it exists so that whenever a dossier-review
 * screen is built, the transition guard is already in place rather than
 * bolted on afterwards.
 */
class DossierStatusService
{
    public function transitionTo(Student $student, DossierStatus $target): Student
    {
        $current = $student->dossier_status;

        if (! $current->canTransitionTo($target)) {
            throw InvalidDossierTransition::from($current, $target);
        }

        $student->setDossierStatus($target);
        $student->save();

        return $student;
    }
}
