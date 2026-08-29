<?php

namespace App\Domain\Students\Policies;

use App\Domain\Students\Models\Student;
use App\Models\User;

/**
 * Student::class already carries the tenant global scope, so a Student
 * instance reaching this policy already belongs to the current tenant
 * *unless* it was fetched via withoutTenantScope() - which is exactly the
 * kind of unscoped lookup that caused the two IDOR bugs found in the legacy
 * app (admin/eleves.php edit+delete, moniteur/evaluation.php). We still
 * re-check structure_id explicitly here rather than trusting the caller.
 */
class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'moniteur']);
    }

    public function view(User $user, Student $student): bool
    {
        if ($student->structure_id !== $user->structure_id) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasRole('moniteur') && $student->instructor_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->hasRole('admin') && $student->structure_id === $user->structure_id;
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->hasRole('admin') && $student->structure_id === $user->structure_id;
    }

    /**
     * Same tenant + ownership rule as view(), reused by the Training domain
     * to gate skill evaluation - this is the exact check that was missing
     * in the legacy moniteur/evaluation.php (fixs.md #4).
     */
    public function evaluate(User $user, Student $student): bool
    {
        return $this->view($user, $student);
    }
}
