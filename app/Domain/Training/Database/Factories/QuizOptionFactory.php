<?php

namespace App\Domain\Training\Database\Factories;

use App\Domain\Training\Models\QuizOption;
use App\Domain\Training\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizOption>
 */
class QuizOptionFactory extends Factory
{
    protected $model = QuizOption::class;

    public function definition(): array
    {
        return [
            'question_id' => QuizQuestion::factory(),
            'text' => $this->faker->words(3, true),
            'is_correct' => false,
        ];
    }
}
