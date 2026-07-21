<?php

namespace App\Domain\Scheduling\Repositories;

use App\Domain\Scheduling\Models\LessonSession;
use Illuminate\Support\Collection;

class EloquentLessonSessionRepository implements LessonSessionRepositoryInterface
{
    public function forInstructorBetween(int $instructorId, string $from, string $to): Collection
    {
        return LessonSession::query()
            ->where('instructor_id', $instructorId)
            ->whereBetween('scheduled_date', [$from, $to])
            ->with('student')
            ->orderBy('scheduled_date')->orderBy('starts_at')
            ->get();
    }

    public function forStudentBetween(int $studentId, string $from, string $to): Collection
    {
        return LessonSession::query()
            ->where('student_id', $studentId)
            ->whereBetween('scheduled_date', [$from, $to])
            ->with('instructor')
            ->orderBy('scheduled_date')->orderBy('starts_at')
            ->get();
    }

    public function create(array $data): LessonSession
    {
        return LessonSession::query()->create($data);
    }

    public function update(LessonSession $session, array $data): LessonSession
    {
        $session->update($data);

        return $session;
    }
}
