<?php

namespace App\Domain\Training\Database\Factories;

use App\Domain\Tenancy\Models\Structure;
use App\Domain\Training\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    protected $model = QuizQuestion::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'prompt' => $this->faker->sentence().'?',
            'category' => 'priorites',
        ];
    }
}
