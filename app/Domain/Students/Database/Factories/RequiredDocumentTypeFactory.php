<?php

namespace App\Domain\Students\Database\Factories;

use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequiredDocumentType>
 */
class RequiredDocumentTypeFactory extends Factory
{
    protected $model = RequiredDocumentType::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'label' => $this->faker->randomElement(["Carte d'identité", 'Justificatif de domicile', 'Photo d\'identité']),
            'position' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
