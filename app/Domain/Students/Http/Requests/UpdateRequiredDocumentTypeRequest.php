<?php

namespace App\Domain\Students\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequiredDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('requiredDocumentType'));
    }

    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
