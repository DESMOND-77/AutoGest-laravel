<?php

namespace App\Domain\Finance\Http\Requests;

use App\Domain\Finance\Enums\LedgerEntryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreLedgerEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(LedgerEntryType::class)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'memo' => ['nullable', 'string', 'max:255'],
            'occurred_on' => ['nullable', 'date'],
        ];
    }
}
