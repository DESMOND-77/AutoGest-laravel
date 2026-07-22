<?php

namespace App\Domain\Settings\Database\Factories;

use App\Domain\Settings\Models\Setting;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'timezone' => 'Africa/Libreville',
            'currency' => 'FCFA',
            'default_theme' => 'light',
        ];
    }
}
