<?php

namespace App\Domain\Training\Http\Resources;

use App\Domain\Training\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin QuizQuestion
 */
class QuizQuestionResource extends JsonResource
{
    /**
     * Explicit field whitelist — options never carry `is_correct`, so a
     * client can never learn the answer before submitting.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'prompt' => $this->prompt,
            'category' => $this->category,
            'options' => $this->options->map(fn ($option) => [
                'id' => $option->id,
                'text' => $option->text,
            ]),
        ];
    }
}
