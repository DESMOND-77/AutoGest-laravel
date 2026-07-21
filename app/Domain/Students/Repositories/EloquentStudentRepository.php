<?php

namespace App\Domain\Students\Repositories;

use App\Domain\Students\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * The tenant filter itself lives in Student::bootBelongsToTenant() (global
 * scope), not here — this repository only needs to worry about query shape,
 * never about which tenant it's allowed to see.
 */
class EloquentStudentRepository implements StudentRepositoryInterface
{
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return Student::query()->orderBy('last_name')->paginate($perPage);
    }

    public function findOrFail(int $id): Student
    {
        return Student::query()->findOrFail($id);
    }

    public function create(array $data): Student
    {
        return Student::query()->create($data);
    }

    public function update(Student $student, array $data): Student
    {
        $student->update($data);

        return $student;
    }

    public function delete(Student $student): void
    {
        $student->delete();
    }
}
