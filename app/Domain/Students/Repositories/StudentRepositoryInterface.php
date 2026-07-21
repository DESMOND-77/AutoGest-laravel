<?php

namespace App\Domain\Students\Repositories;

use App\Domain\Students\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StudentRepositoryInterface
{
    public function paginate(int $perPage = 20): LengthAwarePaginator;

    public function findOrFail(int $id): Student;

    public function create(array $data): Student;

    public function update(Student $student, array $data): Student;

    public function delete(Student $student): void;
}
