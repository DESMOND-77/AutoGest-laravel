<?php

namespace App\Domain\Scheduling\Services;

use App\Domain\Scheduling\Enums\PresenceStatus;
use App\Domain\Scheduling\Models\LessonSession;
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
        return LessonSession::query()
            ->where('instructor_id', $instructorId)
            ->where('scheduled_date', $date)
            ->where('presence', '!=', PresenceStatus::Cancelled->value)
            ->when($excludingSessionId, fn ($query) => $query->where('id', '!=', $excludingSessionId))
            ->where(function ($query) use ($startsAt, $endsAt) {
                $query->where('starts_at', '<', $endsAt)
                    ->where('ends_at', '>', $startsAt);
            })
            ->exists();
    }

    public function validateRange(string $startsAt, string $endsAt): bool
    {
        return Carbon::parse($startsAt)->lt(Carbon::parse($endsAt));
    }
}
