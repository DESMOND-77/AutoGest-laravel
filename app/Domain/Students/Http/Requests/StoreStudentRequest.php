<?php

namespace App\Domain\Students\Http\Requests;

use App\Domain\Students\Enums\CourseType;
use App\Domain\Students\Enums\LicenseCategory;
use App\Domain\Students\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Student::class);
    }

    public function rules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'birth_place' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone_secondary' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string'],
            'neph' => ['nullable', 'string', 'max:50'],
            'license_category' => ['required', new Enum(LicenseCategory::class)],
            'course_type' => ['required', new Enum(CourseType::class)],
            'instructor_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
