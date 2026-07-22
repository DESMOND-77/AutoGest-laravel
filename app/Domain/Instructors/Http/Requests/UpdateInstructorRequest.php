<?php

namespace App\Domain\Instructors\Http\Requests;

use App\Domain\Instructors\Enums\InstructorStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateInstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('instructor'));
    }

    public function rules(): array
    {
        return [
            'license_number' => ['nullable', 'string', 'max:50'],
            'specialties' => ['nullable', 'array'],
            'specialties.*' => ['string', 'max:100'],
            'hire_date' => ['nullable', 'date'],
            'status' => ['nullable', new Enum(InstructorStatus::class)],
        ];
    }
}
