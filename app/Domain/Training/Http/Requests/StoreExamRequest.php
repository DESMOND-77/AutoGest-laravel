<?php

namespace App\Domain\Training\Http\Requests;

use App\Domain\Training\Enums\ExamType;
use App\Domain\Training\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Exam::class);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'type' => ['required', new Enum(ExamType::class)],
            'exam_date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:200'],
            'inspector' => ['nullable', 'string', 'max:150'],
            'fault_count' => ['nullable', 'integer', 'min:0'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
