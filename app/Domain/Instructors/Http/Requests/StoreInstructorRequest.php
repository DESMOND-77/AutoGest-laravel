<?php

namespace App\Domain\Instructors\Http\Requests;

use App\Domain\Instructors\Models\Instructor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Instructor::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users')->where('structure_id', $this->user()->structure_id),
            ],
            'license_number' => ['nullable', 'string', 'max:50'],
            'specialties' => ['nullable', 'array'],
            'specialties.*' => ['string', 'max:100'],
            'hire_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Un compte existe déjà avec cet e-mail pour votre auto-école.',
        ];
    }
}
