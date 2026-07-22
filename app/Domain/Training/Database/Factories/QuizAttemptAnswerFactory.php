<?php

namespace App\Domain\Training\Database\Factories;

use App\Domain\Training\Models\QuizAttempt;
use App\Domain\Training\Models\QuizAttemptAnswer;
use App\Domain\Training\Models\QuizOption;
use App\Domain\Training\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttemptAnswer>
 */
class QuizAttemptAnswerFactory extends Factory
{
    protected $model = QuizAttemptAnswer::class;

    public function definition(): array
    {
        return [
            'attempt_id' => QuizAttempt::factory(),
            'question_id' => QuizQuestion::factory(),
            'option_id' => QuizOption::factory(),
        ];
    }
}
