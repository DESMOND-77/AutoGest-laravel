<?php

namespace App\Domain\Recyclage\Policies;

use App\Domain\Recyclage\Models\RecyclageEntry;
use App\Models\User;

class RecyclageEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, RecyclageEntry $entry): bool
    {
        return $user->hasRole('admin') && $entry->structure_id === $user->structure_id;
    }
}
