<?php

namespace App\Domain\Store\Policies;

use App\Models\User;

class SupplierPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
