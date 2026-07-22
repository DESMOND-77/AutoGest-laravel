<?php

namespace App\Domain\Instructors\Database\Factories;

use App\Domain\Instructors\Models\Instructor;
use App\Domain\Instructors\Models\InstructorAvailability;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstructorAvailability>
 */
class InstructorAvailabilityFactory extends Factory
{
    protected $model = InstructorAvailability::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'instructor_id' => Instructor::factory(),
            'day_of_week' => $this->faker->numberBetween(1, 5),
            'starts_at' => '08:00',
            'ends_at' => '17:00',
        ];
    }
}
