<?php

namespace App\Domain\Students\Http\Requests;

use App\Domain\Students\Enums\DossierStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateDossierStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('student'));
    }

    public function rules(): array
    {
        return [
            'dossier_status' => ['required', new Enum(DossierStatus::class)],
        ];
    }
}
