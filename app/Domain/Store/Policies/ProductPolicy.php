<?php

namespace App\Domain\Store\Policies;

use App\Domain\Store\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasRole('admin') && $product->structure_id === $user->structure_id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->hasRole('admin') && $product->structure_id === $user->structure_id;
    }
}
