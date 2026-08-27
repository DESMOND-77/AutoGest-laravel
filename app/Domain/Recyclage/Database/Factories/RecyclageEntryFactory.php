<?php

namespace App\Domain\Recyclage\Database\Factories;

use App\Domain\Recyclage\Enums\RecyclageMotif;
use App\Domain\Recyclage\Models\RecyclageEntry;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecyclageEntry>
 */
class RecyclageEntryFactory extends Factory
{
    protected $model = RecyclageEntry::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'full_name' => $this->faker->name(),
            'motif' => RecyclageMotif::Test,
            'phone' => $this->faker->phoneNumber(),
            'session_date' => now()->toDateString(),
            'amount' => 15000,
        ];
    }
}
