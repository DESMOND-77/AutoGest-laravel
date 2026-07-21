<?php

namespace App\Domain\Scheduling\Http\Requests;

use App\Domain\Scheduling\Enums\SessionType;
use App\Domain\Scheduling\Models\LessonSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreLessonSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', LessonSession::class);
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'instructor_id' => ['required', 'exists:users,id'],
            'type' => ['required', new Enum(SessionType::class)],
            'scheduled_date' => ['required', 'date'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:200'],
        ];
    }
}
