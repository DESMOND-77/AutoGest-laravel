<?php

namespace App\Domain\Training\Database\Factories;

use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Training\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    protected $model = QuizAttempt::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'student_id' => Student::factory(),
            'score' => 0,
            'total_questions' => 0,
        ];
    }
}
