<?php

namespace App\Domain\Fleet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFuelLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('vehicle'));
    }

    public function rules(): array
    {
        return [
            'liters' => ['required', 'numeric', 'min:0.1'],
            'cost' => ['required', 'numeric', 'min:0'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'filled_on' => ['required', 'date'],
        ];
    }
}
