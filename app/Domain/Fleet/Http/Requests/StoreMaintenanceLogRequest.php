<?php

namespace App\Domain\Fleet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('vehicle'));
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'performed_on' => ['required', 'date'],
            'next_due_on' => ['nullable', 'date'],
        ];
    }
}
