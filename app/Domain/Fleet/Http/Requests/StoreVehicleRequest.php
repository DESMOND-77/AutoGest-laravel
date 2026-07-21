<?php

namespace App\Domain\Fleet\Http\Requests;

use App\Domain\Fleet\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Vehicle::class);
    }

    public function rules(): array
    {
        return [
            'plate' => ['required', 'string', 'max:20'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1990', 'max:'.(date('Y') + 1)],
            'category' => ['required', 'string', 'max:5'],
            'technical_inspection_expires_at' => ['nullable', 'date'],
            'insurance_expires_at' => ['nullable', 'date'],
        ];
    }
}
