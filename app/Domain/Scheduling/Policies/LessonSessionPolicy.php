<?php

namespace App\Domain\Scheduling\Policies;

use App\Domain\Scheduling\Models\LessonSession;
use App\Models\User;

class LessonSessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'moniteur', 'eleve']);
    }

    public function view(User $user, LessonSession $session): bool
    {
        if ($session->structure_id !== $user->structure_id) {
            return false;
        }

        return match (true) {
            $user->hasRole('admin') => true,
            $user->hasRole('moniteur') => $session->instructor_id === $user->id,
            $user->hasRole('eleve') => $session->student->user_id === $user->id,
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, LessonSession $session): bool
    {
        return $user->hasRole('admin') && $session->structure_id === $user->structure_id;
    }

    public function markPresence(User $user, LessonSession $session): bool
    {
        if ($session->structure_id !== $user->structure_id) {
            return false;
        }

        return $user->hasRole('admin')
            || ($user->hasRole('moniteur') && $session->instructor_id === $user->id);
    }
}
