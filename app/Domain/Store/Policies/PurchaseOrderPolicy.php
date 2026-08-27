<?php

namespace App\Domain\Store\Policies;

use App\Domain\Store\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function receive(User $user, PurchaseOrder $order): bool
    {
        return $user->hasRole('admin') && $order->structure_id === $user->structure_id;
    }
}
