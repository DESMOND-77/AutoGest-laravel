<?php

namespace App\Listeners;

use App\Domain\Notifications\Notifications\AlertNotification;
use App\Domain\Students\Events\StudentStageChanged;

class NotifyInstructorOnStageChange
{
    public function handle(StudentStageChanged $event): void
    {
        $student = $event->student;
        $instructor = $student->instructor;

        if (! $instructor) {
            return;
        }

        $instructor->notify(new AlertNotification(
            title: 'Étape élève mise à jour',
            message: "{$student->fullName()} est passé(e) à l'étape « {$event->to->label()} »",
            link: route('students.show', $student, absolute: false),
        ));
    }
}
