<?php

namespace App\Domain\Training\Http\Requests;

use App\Domain\Training\Enums\ExamResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateExamResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('exam'));
    }

    public function rules(): array
    {
        return [
            'result' => ['required', new Enum(ExamResult::class)],
            'fault_count' => ['nullable', 'integer', 'min:0'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
