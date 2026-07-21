<?php

namespace App\Domain\Scheduling\Services;

use App\Domain\Scheduling\Enums\PresenceStatus;
use App\Domain\Scheduling\Exceptions\SchedulingConflict;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Scheduling\Repositories\LessonSessionRepositoryInterface;

class SchedulingService
{
    public function __construct(
        private readonly LessonSessionRepositoryInterface $sessions,
        private readonly ConflictRule $conflictRule,
    ) {}

    public function schedule(array $data): LessonSession
    {
        $this->guard($data['instructor_id'], $data['scheduled_date'], $data['starts_at'], $data['ends_at']);

        return $this->sessions->create($data);
    }

    public function reschedule(LessonSession $session, array $data): LessonSession
    {
        $this->guard(
            $data['instructor_id'] ?? $session->instructor_id,
            $data['scheduled_date'] ?? $session->scheduled_date->toDateString(),
            $data['starts_at'] ?? $session->starts_at,
            $data['ends_at'] ?? $session->ends_at,
            $session->id,
        );

        return $this->sessions->update($session, $data);
    }

    public function markPresence(LessonSession $session, PresenceStatus $status): LessonSession
    {
        return $this->sessions->update($session, ['presence' => $status->value]);
    }

    private function guard(int $instructorId, string $date, string $startsAt, string $endsAt, ?int $excluding = null): void
    {
        if (! $this->conflictRule->validateRange($startsAt, $endsAt)) {
            throw SchedulingConflict::invalidRange();
        }

        if ($this->conflictRule->hasConflict($instructorId, $date, $startsAt, $endsAt, $excluding)) {
            throw SchedulingConflict::instructorBusy();
        }
    }
}
