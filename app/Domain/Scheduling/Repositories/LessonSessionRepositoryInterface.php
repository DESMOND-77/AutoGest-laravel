<?php

namespace App\Domain\Scheduling\Repositories;

use App\Domain\Scheduling\Models\LessonSession;
use Illuminate\Support\Collection;

interface LessonSessionRepositoryInterface
{
    public function forInstructorBetween(int $instructorId, string $from, string $to): Collection;

    public function forStudentBetween(int $studentId, string $from, string $to): Collection;

    public function create(array $data): LessonSession;

    public function update(LessonSession $session, array $data): LessonSession;
}
