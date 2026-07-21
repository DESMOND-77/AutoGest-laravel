<?php

namespace App\Domain\Students\Services;

use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Repositories\StudentRepositoryInterface;

class EnrollmentService
{
    public function __construct(
        private readonly StudentRepositoryInterface $students,
    ) {}

    public function register(array $data): Student
    {
        $data['lifecycle_stage'] ??= LifecycleStage::Prospect->value;
        $data['registered_at'] ??= now()->toDateString();

        return $this->students->create($data);
    }

    public function update(Student $student, array $data): Student
    {
        return $this->students->update($student, $data);
    }
}
