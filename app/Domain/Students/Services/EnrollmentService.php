<?php

namespace App\Domain\Students\Services;

use App\Domain\Students\Models\Student;
use App\Domain\Students\Repositories\StudentRepositoryInterface;

class EnrollmentService
{
    public function __construct(
        private readonly StudentRepositoryInterface $students,
    ) {}

    /**
     * lifecycle_stage/dossier_status are intentionally absent from $data -
     * they're guarded (see Student::setLifecycleStage/setDossierStatus) and
     * every new student starts at the same defaults ('prospect'/
     * 'incomplete'), enforced at the database column level.
     */
    public function register(array $data): Student
    {
        $data['registered_at'] ??= now()->toDateString();

        return $this->students->create($data);
    }

    public function update(Student $student, array $data): Student
    {
        return $this->students->update($student, $data);
    }
}
