<?php

namespace App\Domain\Students\Policies;

use App\Domain\Students\Models\StudentRegistrationLink;
use App\Models\User;

/**
 * Same shape as StudentPolicy: admin role + explicit same-tenant check, so
 * an admin can never view, revoke or regenerate another tenant's link even
 * if BelongsToTenant's global scope were ever bypassed upstream.
 */
class StudentRegistrationLinkPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function revoke(User $user, StudentRegistrationLink $link): bool
    {
        return $user->hasRole('admin') && $link->structure_id === $user->structure_id;
    }

    public function regenerate(User $user, StudentRegistrationLink $link): bool
    {
        return $this->revoke($user, $link);
    }
}
