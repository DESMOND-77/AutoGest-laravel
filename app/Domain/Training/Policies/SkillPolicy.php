<?php

namespace App\Domain\Training\Policies;

use App\Domain\Training\Models\Skill;
use App\Models\User;

class SkillPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'moniteur']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Skill $skill): bool
    {
        return $user->hasRole('admin') && $skill->structure_id === $user->structure_id;
    }
}
