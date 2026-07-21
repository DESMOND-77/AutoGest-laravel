<?php

namespace App\Domain\Documents\Http\Requests;

use App\Domain\Documents\Enums\DocumentType;
use App\Domain\Documents\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Document::class);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(DocumentType::class)],
            'file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,webp'],
            'expires_at' => ['nullable', 'date'],
        ];
    }
}
