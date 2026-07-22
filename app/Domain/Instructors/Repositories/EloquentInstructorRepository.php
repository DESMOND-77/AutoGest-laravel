<?php

namespace App\Domain\Instructors\Repositories;

use App\Domain\Instructors\Models\Instructor;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentInstructorRepository implements InstructorRepositoryInterface
{
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return Instructor::query()->with('user')->orderBy('id')->paginate($perPage);
    }

    public function forUser(User $user): ?Instructor
    {
        return Instructor::query()->where('user_id', $user->id)->first();
    }

    public function create(array $data): Instructor
    {
        return Instructor::query()->create($data);
    }
}
