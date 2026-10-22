<?php

namespace App\Domain\Scheduling\Http\Requests;

use App\Domain\Scheduling\Enums\SessionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateLessonSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('session'));
    }

    public function rules(): array
    {
        $structureId = $this->user()->structure_id;

        return [
            'instructor_id' => ['required', Rule::exists('users', 'id')->where('structure_id', $structureId)],
            'vehicle_id' => [
                Rule::requiredIf(fn () => $this->input('type') === SessionType::Practical->value),
                'nullable',
                Rule::exists('vehicles', 'id')->where('structure_id', $structureId),
            ],
            'type' => ['required', new Enum(SessionType::class)],
            'scheduled_date' => ['required', 'date'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
        ];
    }
}
