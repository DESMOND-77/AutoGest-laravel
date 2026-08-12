<?php

namespace App\Domain\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('cancel', $this->route('payment'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
