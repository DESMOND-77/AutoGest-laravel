<?php

namespace App\Domain\Finance\Http\Requests;

use App\Domain\Finance\Enums\PaymentMethod;
use App\Domain\Finance\Models\Invoice;
use Closure;
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
            'amount' => ['required', 'numeric', 'min:0.01', $this->notExceedingBalanceDue()],
            'method' => ['required', new Enum(PaymentMethod::class)],
            'paid_at' => ['nullable', 'date'],
        ];
    }

    /**
     * A payment larger than what's left on the invoice was previously
     * accepted silently, with no notion of overpayment/credit - see
     * docs/audit/business-workflow.md FIN-01.
     */
    private function notExceedingBalanceDue(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            /** @var Invoice $invoice */
            $invoice = $this->route('invoice');

            $balanceDue = bcsub((string) $invoice->amount_due, (string) $invoice->amount_paid, 2);

            if (bccomp((string) $value, $balanceDue, 2) > 0) {
                $fail('Le montant dépasse le solde restant dû ('.number_format((float) $balanceDue, 0, ',', ' ').' FCFA).');
            }
        };
    }
}
