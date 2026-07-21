<?php

namespace App\Domain\Documents\Policies;

use App\Domain\Documents\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Document $document): bool
    {
        return $user->hasAnyRole(['admin', 'moniteur']) && $document->structure_id === $user->structure_id;
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasRole('admin') && $document->structure_id === $user->structure_id;
    }
}
