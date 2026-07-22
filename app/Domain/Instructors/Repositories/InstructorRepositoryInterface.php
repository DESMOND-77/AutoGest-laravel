<?php

namespace App\Domain\Instructors\Repositories;

use App\Domain\Instructors\Models\Instructor;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InstructorRepositoryInterface
{
    public function paginate(int $perPage = 20): LengthAwarePaginator;

    public function forUser(User $user): ?Instructor;

    public function create(array $data): Instructor;
}
