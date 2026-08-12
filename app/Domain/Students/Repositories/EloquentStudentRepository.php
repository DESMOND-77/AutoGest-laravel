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
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Student::query()->with('instructor');

        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($stage = $filters['stage'] ?? null) {
            $query->where('lifecycle_stage', $stage);
        }

        if ($licenseCategory = $filters['license_category'] ?? null) {
            $query->where('license_category', $licenseCategory);
        }

        if ($courseType = $filters['course_type'] ?? null) {
            $query->where('course_type', $courseType);
        }

        if ($instructorId = $filters['instructor_id'] ?? null) {
            $query->where('instructor_id', $instructorId);
        }

        if ($from = $filters['registered_from'] ?? null) {
            $query->where('registered_at', '>=', $from);
        }

        if ($to = $filters['registered_to'] ?? null) {
            $query->where('registered_at', '<=', $to);
        }

        return $query->orderBy('last_name')->paginate($perPage)->withQueryString();
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
