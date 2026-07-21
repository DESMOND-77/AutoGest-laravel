<?php

namespace App\Domain\Store\Database\Factories;

use App\Domain\Store\Models\Supplier;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'name' => $this->faker->company(),
        ];
    }
}
