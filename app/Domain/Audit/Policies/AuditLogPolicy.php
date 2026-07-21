<?php

namespace App\Domain\Audit\Policies;

use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'superadmin']);
    }
}
