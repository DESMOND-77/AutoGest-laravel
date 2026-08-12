<?php

namespace App\Domain\Students\Repositories;

use App\Domain\Students\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StudentRepositoryInterface
{
    /**
     * @param  array{search?: string, stage?: string, license_category?: string, course_type?: string, instructor_id?: int, registered_from?: string, registered_to?: string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function findOrFail(int $id): Student;

    public function create(array $data): Student;

    public function update(Student $student, array $data): Student;

    public function delete(Student $student): void;
}
