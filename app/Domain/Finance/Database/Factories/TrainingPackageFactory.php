<?php

namespace App\Domain\Finance\Database\Factories;

use App\Domain\Finance\Models\TrainingPackage;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingPackage>
 */
class TrainingPackageFactory extends Factory
{
    protected $model = TrainingPackage::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'name' => 'Forfait '.$this->faker->randomElement(['Standard', 'Accéléré', 'Premium']),
            'hours' => $this->faker->numberBetween(15, 25),
            'license_category' => 'B',
            'price' => $this->faker->numberBetween(150000, 350000),
            'active' => true,
        ];
    }
}
