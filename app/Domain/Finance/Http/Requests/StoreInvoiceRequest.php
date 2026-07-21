<?php

namespace App\Domain\Finance\Http\Requests;

use App\Domain\Finance\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Invoice::class);
    }

    public function rules(): array
    {
        return [
            'training_package_id' => ['nullable', 'exists:training_packages,id'],
            'label' => ['nullable', 'string', 'max:200'],
            'amount_due' => ['required_without:training_package_id', 'nullable', 'numeric', 'min:0'],
            'issued_at' => ['nullable', 'date'],
        ];
    }
}
