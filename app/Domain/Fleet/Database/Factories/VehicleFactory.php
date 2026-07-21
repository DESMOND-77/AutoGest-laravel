<?php

namespace App\Domain\Fleet\Database\Factories;

use App\Domain\Fleet\Enums\VehicleStatus;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'plate' => strtoupper($this->faker->unique()->bothify('??-###-??')),
            'brand' => $this->faker->randomElement(['Toyota', 'Renault', 'Peugeot']),
            'model' => $this->faker->word(),
            'year' => $this->faker->numberBetween(2015, 2024),
            'category' => 'B',
            'mileage' => $this->faker->numberBetween(1000, 80000),
            'status' => VehicleStatus::Active,
        ];
    }
}
