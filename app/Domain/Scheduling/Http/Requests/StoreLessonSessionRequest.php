<?php

namespace App\Domain\Scheduling\Http\Requests;

use App\Domain\Scheduling\Enums\SessionType;
use App\Domain\Scheduling\Models\LessonSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreLessonSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', LessonSession::class);
    }

    public function rules(): array
    {
        $structureId = $this->user()->structure_id;

        return [
            'student_id' => ['required', Rule::exists('students', 'id')->where('structure_id', $structureId)],
            'instructor_id' => ['required', Rule::exists('users', 'id')->where('structure_id', $structureId)],
            // A "Conduite" (Practical) session always needs a car — see
            // SCHED-02 in docs/audit/business-workflow.md. Other session
            // types (theory, code, mock exam) stay vehicle-optional.
            'vehicle_id' => [
                Rule::requiredIf(fn () => $this->input('type') === SessionType::Practical->value),
                'nullable',
                Rule::exists('vehicles', 'id')->where('structure_id', $structureId),
            ],
            'type' => ['required', new Enum(SessionType::class)],
            'scheduled_date' => ['required', 'date'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:200'],
        ];
    }
}
