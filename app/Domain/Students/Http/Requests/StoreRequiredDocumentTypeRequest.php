<?php

namespace App\Domain\Students\Http\Requests;

use App\Domain\Students\Models\RequiredDocumentType;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequiredDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', RequiredDocumentType::class);
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:150'],
        ];
    }
}
