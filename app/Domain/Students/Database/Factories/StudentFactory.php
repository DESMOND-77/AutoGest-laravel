<?php

namespace App\Domain\Students\Database\Factories;

use App\Domain\Students\Enums\CourseType;
use App\Domain\Students\Enums\LicenseCategory;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'last_name' => $this->faker->lastName(),
            'first_name' => $this->faker->firstName(),
            'phone' => $this->faker->phoneNumber(),
            'license_category' => LicenseCategory::B,
            'course_type' => CourseType::Normal,
            'lifecycle_stage' => LifecycleStage::Prospect,
            'registered_at' => now()->toDateString(),
        ];
    }
}
