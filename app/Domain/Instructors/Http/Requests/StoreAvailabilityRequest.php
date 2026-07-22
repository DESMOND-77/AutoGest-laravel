<?php

namespace App\Domain\Instructors\Http\Requests;

use App\Domain\Instructors\Models\Instructor;
use Illuminate\Foundation\Http\FormRequest;

class StoreAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Instructor $instructor */
        $instructor = $this->route('instructor');

        return $this->user()->can('update', $instructor);
    }

    public function rules(): array
    {
        return [
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
        ];
    }
}
