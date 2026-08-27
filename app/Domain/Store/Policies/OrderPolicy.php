<?php

namespace App\Domain\Store\Policies;

use App\Domain\Store\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->hasRole('admin') && $order->structure_id === $user->structure_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function cancel(User $user, Order $order): bool
    {
        return $user->hasRole('admin') && $order->structure_id === $user->structure_id;
    }
}
