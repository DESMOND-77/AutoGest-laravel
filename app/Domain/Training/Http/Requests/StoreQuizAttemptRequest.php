<?php

namespace App\Domain\Training\Http\Requests;

use App\Domain\Training\Models\QuizAttempt;
use Illuminate\Foundation\Http\FormRequest;

class StoreQuizAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', QuizAttempt::class);
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*' => ['required', 'integer', 'exists:quiz_options,id'],
        ];
    }
}
