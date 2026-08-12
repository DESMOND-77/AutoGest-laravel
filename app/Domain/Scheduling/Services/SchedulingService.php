<?php

namespace App\Domain\Scheduling\Services;

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Scheduling\Enums\PresenceStatus;
use App\Domain\Scheduling\Exceptions\SchedulingConflict;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Scheduling\Repositories\LessonSessionRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SchedulingService
{
    public function __construct(
        private readonly LessonSessionRepositoryInterface $sessions,
        private readonly ConflictRule $conflictRule,
    ) {}

    public function schedule(array $data): LessonSession
    {
        return DB::transaction(function () use ($data) {
            $this->lockParticipants($data['instructor_id'], $data['vehicle_id'] ?? null);

            $this->guard(
                $data['instructor_id'],
                $data['vehicle_id'] ?? null,
                $data['scheduled_date'],
                $data['starts_at'],
                $data['ends_at'],
            );

            return $this->sessions->create($data);
        });
    }

    public function reschedule(LessonSession $session, array $data): LessonSession
    {
        return DB::transaction(function () use ($session, $data) {
            $instructorId = $data['instructor_id'] ?? $session->instructor_id;
            $vehicleId = array_key_exists('vehicle_id', $data) ? $data['vehicle_id'] : $session->vehicle_id;

            $this->lockParticipants($instructorId, $vehicleId);

            $this->guard(
                $instructorId,
                $vehicleId,
                $data['scheduled_date'] ?? $session->scheduled_date->toDateString(),
                $data['starts_at'] ?? $session->starts_at,
                $data['ends_at'] ?? $session->ends_at,
                $session->id,
            );

            return $this->sessions->update($session, $data);
        });
    }

    /**
     * Locks the instructor's (and, if any, the vehicle's) row for the
     * duration of the transaction, so two concurrent requests booking the
     * same instructor/vehicle serialize instead of both passing the
     * conflict check before either insert lands — see SCHED-03/TECH-08 in
     * docs/audit/. A plain SELECT...FOR UPDATE over lesson_sessions doesn't
     * protect against this on its own: a brand-new booking with no existing
     * overlapping row locks nothing, so two transactions can both see "no
     * conflict" and both insert. Locking the owning row instead serializes
     * every booking attempt for that instructor/vehicle regardless of
     * whether a matching session already exists.
     */
    private function lockParticipants(int $instructorId, ?int $vehicleId): void
    {
        User::query()->whereKey($instructorId)->lockForUpdate()->first();

        if ($vehicleId) {
            Vehicle::query()->whereKey($vehicleId)->lockForUpdate()->first();
        }
    }

    public function markPresence(LessonSession $session, PresenceStatus $status): LessonSession
    {
        return $this->sessions->update($session, ['presence' => $status->value]);
    }

    private function guard(
        int $instructorId,
        ?int $vehicleId,
        string $date,
        string $startsAt,
        string $endsAt,
        ?int $excluding = null,
    ): void {
        if (! $this->conflictRule->validateRange($startsAt, $endsAt)) {
            throw SchedulingConflict::invalidRange();
        }

        if ($this->conflictRule->hasConflict($instructorId, $date, $startsAt, $endsAt, $excluding)) {
            throw SchedulingConflict::instructorBusy();
        }

        if ($vehicleId && $this->conflictRule->hasVehicleConflict($vehicleId, $date, $startsAt, $endsAt, $excluding)) {
            throw SchedulingConflict::vehicleBusy();
        }
    }
}
