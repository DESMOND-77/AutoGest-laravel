<?php

namespace App\Domain\Scheduling\Services;

use App\Domain\Scheduling\Enums\PresenceStatus;
use App\Domain\Scheduling\Enums\SessionType;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Students\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Session duration is always computed from starts_at/ends_at at read time -
 * no stored duration column, so there is nothing to keep in sync. Scoped to
 * one (student, instructor) pair on purpose: a moniteur's route sheet for a
 * student only reflects sessions they themselves conducted.
 */
class StudentSessionSummaryService
{
    /**
     * @return array{total: int, present: int, absent: int, practicalHoursCompleted: float, sessions: Collection<int, LessonSession>}
     */
    public function summarize(Student $student, User $instructor): array
    {
        $sessions = LessonSession::query()
            ->where('student_id', $student->id)
            ->where('instructor_id', $instructor->id)
            ->orderByDesc('scheduled_date')
            ->get();

        $practicalHoursCompleted = $sessions
            ->filter(fn (LessonSession $session) => $session->presence === PresenceStatus::Present && $session->type === SessionType::Practical)
            ->sum(fn (LessonSession $session) => Carbon::parse($session->starts_at)->diffInMinutes(Carbon::parse($session->ends_at)) / 60);

        return [
            'total' => $sessions->count(),
            'present' => $sessions->where('presence', PresenceStatus::Present)->count(),
            'absent' => $sessions->where('presence', PresenceStatus::Absent)->count(),
            'practicalHoursCompleted' => round($practicalHoursCompleted, 2),
            'sessions' => $sessions,
        ];
    }
}
