<?php

namespace App\Domain\Tenancy\Policies;

use App\Domain\Tenancy\Models\Structure;
use App\Models\User;

/**
 * Structure is the tenant itself, so there is no per-row structure_id check
 * to make here (unlike every other Policy in the app) — superadmin either
 * manages every tenant or none. The route middleware (role:superadmin)
 * already enforces this; this Policy exists so StructureManagementController
 * follows the same authorize()-per-action pattern as every other controller
 * instead of being the one silent exception. See TECH-02 in
 * docs/audit/technical-audit.md.
 */
class StructurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('superadmin');
    }

    public function update(User $user, Structure $structure): bool
    {
        return $user->hasRole('superadmin');
    }

    public function delete(User $user, Structure $structure): bool
    {
        return $user->hasRole('superadmin');
    }
}
