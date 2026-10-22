<?php

namespace App\Domain\Students\Policies;

use App\Domain\Students\Models\RequiredDocumentType;
use App\Models\User;

class RequiredDocumentTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, RequiredDocumentType $type): bool
    {
        return $user->hasRole('admin') && $type->structure_id === $user->structure_id;
    }
}
