<?php

namespace App\Domain\Instructors\Database\Factories;

use App\Domain\Instructors\Enums\InstructorStatus;
use App\Domain\Instructors\Models\Instructor;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Instructor>
 */
class InstructorFactory extends Factory
{
    protected $model = Instructor::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'user_id' => User::factory(),
            'license_number' => strtoupper($this->faker->bothify('MON-####')),
            'specialties' => [],
            'hire_date' => $this->faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'status' => InstructorStatus::Active,
        ];
    }
}
