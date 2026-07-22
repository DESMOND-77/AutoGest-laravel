<?php

namespace App\Domain\Instructors\Http\Requests;

use App\Domain\Instructors\Models\Instructor;
use Illuminate\Foundation\Http\FormRequest;

class StoreInstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Instructor::class);
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'specialties' => ['nullable', 'array'],
            'specialties.*' => ['string', 'max:100'],
            'hire_date' => ['nullable', 'date'],
        ];
    }
}
