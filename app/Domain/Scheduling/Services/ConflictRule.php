<?php

namespace App\Domain\Scheduling\Services;

use App\Domain\Scheduling\Enums\PresenceStatus;
use App\Domain\Scheduling\Models\LessonSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Centralizes what the legacy admin/planning.php did with a hand-rolled
 * fixed-hour grid: it matched a session into a cell by string-comparing
 * substr($s['heure_debut'],0,5) against "$h:00", which (per fixs.md #2) was
 * broken by an hour-format mismatch, and which structurally could never
 * represent a half-hour session at all — the grid only had whole-hour rows.
 * This rule instead does a real time-range overlap check, so any start/end
 * time works and there is exactly one implementation of the rule, not one
 * per page that happens to need it.
 */
class ConflictRule
{
    public function hasConflict(
        int $instructorId,
        string $date,
        string $startsAt,
        string $endsAt,
        ?int $excludingSessionId = null,
    ): bool {
        return $this->overlapQuery('instructor_id', $instructorId, $date, $startsAt, $endsAt, $excludingSessionId)->exists();
    }

    /**
     * Same overlap check as hasConflict(), scoped to a vehicle instead of an
     * instructor — a car can't be double-booked any more than a moniteur can.
     */
    public function hasVehicleConflict(
        int $vehicleId,
        string $date,
        string $startsAt,
        string $endsAt,
        ?int $excludingSessionId = null,
    ): bool {
        return $this->overlapQuery('vehicle_id', $vehicleId, $date, $startsAt, $endsAt, $excludingSessionId)->exists();
    }

    private function overlapQuery(
        string $column,
        int $id,
        string $date,
        string $startsAt,
        string $endsAt,
        ?int $excludingSessionId,
    ): Builder {
        return LessonSession::query()
            ->where($column, $id)
            ->where('scheduled_date', $date)
            ->where('presence', '!=', PresenceStatus::Cancelled->value)
            ->when($excludingSessionId, fn ($query) => $query->where('id', '!=', $excludingSessionId))
            ->where(function ($query) use ($startsAt, $endsAt) {
                $query->where('starts_at', '<', $endsAt)
                    ->where('ends_at', '>', $startsAt);
            });
    }

    public function validateRange(string $startsAt, string $endsAt): bool
    {
        return Carbon::parse($startsAt)->lt(Carbon::parse($endsAt));
    }
}
