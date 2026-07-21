<?php

namespace App\Domain\Finance\Http\Requests;

use App\Domain\Finance\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('recordPayment', $this->route('invoice'));
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', new Enum(PaymentMethod::class)],
            'paid_at' => ['nullable', 'date'],
        ];
    }
}
