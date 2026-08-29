<?php

namespace App\Domain\Students\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDossierDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('eleve') ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,webp'],
        ];
    }
}
