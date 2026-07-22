<?php

namespace App\Domain\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'display_name' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30'],
            'support_email' => ['nullable', 'email', 'max:150'],
            'timezone' => ['nullable', 'string', 'max:60'],
            'currency' => ['nullable', 'string', 'max:10'],
            'default_theme' => ['nullable', 'string', 'in:light,dark'],
        ];
    }
}
