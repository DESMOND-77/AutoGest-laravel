<?php

namespace App\Domain\Documents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectDossierDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('document'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
