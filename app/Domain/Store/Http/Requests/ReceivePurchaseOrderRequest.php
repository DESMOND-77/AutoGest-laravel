<?php

namespace App\Domain\Store\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('receive', $this->route('purchaseOrder'));
    }

    public function rules(): array
    {
        return [
            'received' => ['required', 'array'],
            'received.*' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
