<?php

namespace App\Domain\Finance\Http\Requests;

use App\Domain\Finance\Models\TrainingPackage;
use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingPackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', TrainingPackage::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'hours' => ['nullable', 'integer', 'min:1'],
            'license_category' => ['required', 'string', 'max:5'],
            'price' => ['required', 'numeric', 'min:0'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
