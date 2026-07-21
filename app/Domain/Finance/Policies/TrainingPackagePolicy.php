<?php

namespace App\Domain\Finance\Policies;

use App\Domain\Finance\Models\TrainingPackage;
use App\Models\User;

class TrainingPackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, TrainingPackage $package): bool
    {
        return $user->hasRole('admin') && $package->structure_id === $user->structure_id;
    }

    public function delete(User $user, TrainingPackage $package): bool
    {
        return $user->hasRole('admin') && $package->structure_id === $user->structure_id;
    }
}
