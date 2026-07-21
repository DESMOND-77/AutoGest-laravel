<?php

namespace App\Domain\Tenancy\Database\Factories;

use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Structure>
 */
class StructureFactory extends Factory
{
    protected $model = Structure::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'status' => StructureStatus::Active,
        ];
    }
}
