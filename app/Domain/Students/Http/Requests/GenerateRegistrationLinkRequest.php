<?php

namespace App\Domain\Students\Http\Requests;

use App\Domain\Students\Models\StudentRegistrationLink;
use Illuminate\Foundation\Http\FormRequest;

class GenerateRegistrationLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', StudentRegistrationLink::class);
    }

    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:100'],
        ];
    }
}
