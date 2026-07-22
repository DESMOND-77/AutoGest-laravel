<?php

namespace App\Domain\Training\Policies;

use App\Domain\Training\Models\QuizAttempt;
use App\Models\User;

class QuizAttemptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'moniteur', 'eleve']);
    }

    public function view(User $user, QuizAttempt $attempt): bool
    {
        if ($attempt->structure_id !== $user->structure_id) {
            return false;
        }

        return match (true) {
            $user->hasRole('admin') => true,
            $user->hasRole('moniteur') => $attempt->student->instructor_id === $user->id,
            $user->hasRole('eleve') => $attempt->student->user_id === $user->id,
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return $user->hasRole('eleve');
    }
}
