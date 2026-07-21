<?php

namespace App\Domain\Fleet\Policies;

use App\Domain\Fleet\Models\Vehicle;
use App\Models\User;

class VehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'moniteur']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $user->hasRole('admin') && $vehicle->structure_id === $user->structure_id;
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $user->hasRole('admin') && $vehicle->structure_id === $user->structure_id;
    }
}
