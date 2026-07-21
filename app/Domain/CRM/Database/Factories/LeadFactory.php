<?php

namespace App\Domain\CRM\Database\Factories;

use App\Domain\CRM\Enums\LeadStatus;
use App\Domain\CRM\Models\Lead;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'status' => LeadStatus::New,
        ];
    }
}
