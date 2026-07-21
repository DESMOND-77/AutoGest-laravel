<?php

namespace App\Domain\Students\Listeners;

use App\Domain\Students\Events\StudentStageChanged;
use Illuminate\Support\Facades\Log;

/**
 * Placeholder until the Notifications domain (roadmap phase 5) exists to
 * turn lifecycle events into real notifications/emails. Keeping this as its
 * own listener means swapping the placeholder out later doesn't touch
 * LifecycleService.
 */
class LogStageChange
{
    public function handle(StudentStageChanged $event): void
    {
        Log::info('Student lifecycle stage changed', [
            'student_id' => $event->student->id,
            'structure_id' => $event->student->structure_id,
            'from' => $event->from->value,
            'to' => $event->to->value,
        ]);
    }
}
