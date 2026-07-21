<?php

namespace App\Domain\Training\Database\Factories;

use App\Domain\Tenancy\Models\Structure;
use App\Domain\Training\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Skill>
 */
class SkillFactory extends Factory
{
    protected $model = Skill::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'code' => strtoupper($this->faker->unique()->lexify('SK-????')),
            'label' => $this->faker->sentence(3),
            'category' => $this->faker->randomElement(['Maîtrise du véhicule', 'Circulation', 'Manœuvres']),
            'position' => 0,
        ];
    }
}
