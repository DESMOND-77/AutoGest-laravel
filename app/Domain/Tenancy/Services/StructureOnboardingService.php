<?php

namespace App\Domain\Tenancy\Services;

use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Public self-service signup: creates a pending Structure plus its first
 * admin account in one transaction. No auto-login — access only opens once
 * a super-admin activates the structure, matching the legacy app's
 * inscription_structure.php behaviour.
 */
class StructureOnboardingService
{
    public function register(array $data): Structure
    {
        return DB::transaction(function () use ($data) {
            $structure = Structure::create([
                'name' => $data['school_name'],
                'email' => $data['admin_email'],
                'phone' => $data['school_phone'] ?? null,
                'status' => StructureStatus::Pending,
            ]);

            $admin = User::create([
                'structure_id' => $structure->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['password']),
            ]);

            $admin->assignRole('admin');

            return $structure;
        });
    }
}
