<?php

namespace App\Domain\Fleet\Database\Factories;

use App\Domain\Fleet\Models\MaintenanceLog;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MaintenanceLog>
 */
class MaintenanceLogFactory extends Factory
{
    protected $model = MaintenanceLog::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'vehicle_id' => Vehicle::factory(),
            'type' => 'vidange',
            'cost' => 25000,
            'performed_on' => now()->toDateString(),
        ];
    }
}
