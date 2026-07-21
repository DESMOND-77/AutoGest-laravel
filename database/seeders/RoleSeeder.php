<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * The four roles from the legacy app (ROLE_SUPERADMIN, ROLE_ADMIN,
 * ROLE_MONITEUR, ROLE_ELEVE). Permissions per role are added domain by
 * domain as each module is migrated, not all at once here.
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['superadmin', 'admin', 'moniteur', 'eleve'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
