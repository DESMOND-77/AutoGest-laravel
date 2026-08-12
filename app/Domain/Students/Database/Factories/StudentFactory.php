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
            'registered_at' => now()->toDateString(),
        ];
    }

    /**
     * lifecycle_stage is a guarded column (see Student::setLifecycleStage) —
     * plain mass assignment silently ignores it, so a factory state is the
     * only way for a test to create a student already past the 'prospect'
     * database default.
     */
    public function stage(LifecycleStage $stage): static
    {
        return $this->afterCreating(function (Student $student) use ($stage) {
            $student->setLifecycleStage($stage);
            $student->save();
        });
    }
}
