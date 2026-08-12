<?php

namespace App\Domain\Students\Listeners;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Students\Events\StudentStageChanged;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Runs synchronously in the same request as the transition (not queued), so
 * Auth::user() here is reliably the admin/moniteur who triggered it — there
 * is no separate "who did this" field on Student itself to fall back on.
 *
 * Also writes to AuditLog: previously only Log::info was recorded, meaning
 * lifecycle transitions never showed up on the Audit screen despite being
 * one of the most consequential actions an admin can take on a student. See
 * WF-04 in docs/audit/business-workflow.md.
 */
class LogStageChange
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function handle(StudentStageChanged $event): void
    {
        Log::info('Student lifecycle stage changed', [
            'student_id' => $event->student->id,
            'structure_id' => $event->student->structure_id,
            'from' => $event->from->value,
            'to' => $event->to->value,
        ]);

        $this->audit->log(
            action: 'student.stage_changed',
            auditable: $event->student,
            old: ['lifecycle_stage' => $event->from->value],
            new: ['lifecycle_stage' => $event->to->value],
            actor: Auth::user(),
        );
    }
}
