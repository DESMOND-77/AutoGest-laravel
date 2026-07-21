<?php

namespace App\Domain\CRM\Policies;

use App\Domain\CRM\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->hasRole('admin') && $lead->structure_id === $user->structure_id;
    }
}
