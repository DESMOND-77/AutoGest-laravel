<?php

namespace App\Domain\Settings\Policies;

use App\Domain\Settings\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    public function view(User $user, Setting $setting): bool
    {
        return $user->hasRole('admin') && $setting->structure_id === $user->structure_id;
    }

    public function update(User $user, Setting $setting): bool
    {
        return $user->hasRole('admin') && $setting->structure_id === $user->structure_id;
    }
}
