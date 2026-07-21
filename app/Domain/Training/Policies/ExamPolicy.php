<?php

namespace App\Domain\Training\Policies;

use App\Domain\Training\Models\Exam;
use App\Models\User;

class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'moniteur']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Exam $exam): bool
    {
        return $user->hasRole('admin') && $exam->structure_id === $user->structure_id;
    }
}
