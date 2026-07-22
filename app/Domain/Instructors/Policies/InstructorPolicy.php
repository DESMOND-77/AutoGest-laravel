<?php

namespace App\Domain\Instructors\Policies;

use App\Domain\Instructors\Models\Instructor;
use App\Models\User;

class InstructorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'moniteur']);
    }

    public function view(User $user, Instructor $instructor): bool
    {
        if ($instructor->structure_id !== $user->structure_id) {
            return false;
        }

        return $user->hasRole('admin')
            || ($user->hasRole('moniteur') && $instructor->user_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Instructor $instructor): bool
    {
        if ($instructor->structure_id !== $user->structure_id) {
            return false;
        }

        return $user->hasRole('admin')
            || ($user->hasRole('moniteur') && $instructor->user_id === $user->id);
    }

    public function delete(User $user, Instructor $instructor): bool
    {
        return $user->hasRole('admin') && $instructor->structure_id === $user->structure_id;
    }
}
