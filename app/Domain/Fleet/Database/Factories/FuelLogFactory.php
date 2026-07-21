<?php

namespace App\Domain\Fleet\Database\Factories;

use App\Domain\Fleet\Models\FuelLog;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FuelLog>
 */
class FuelLogFactory extends Factory
{
    protected $model = FuelLog::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'vehicle_id' => Vehicle::factory(),
            'liters' => 30,
            'cost' => 20000,
            'filled_on' => now()->toDateString(),
        ];
    }
}
