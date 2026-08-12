<?php

namespace App\Domain\Finance\Http\Controllers;

use App\Domain\Finance\Exceptions\PaymentAlreadyCancelled;
use App\Domain\Finance\Http\Requests\CancelPaymentRequest;
use App\Domain\Finance\Http\Requests\StorePaymentRequest;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Finance\Services\PaymentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {}

    public function store(StorePaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->payments->record($invoice, $request->validated(), Auth::user());

        return redirect()->route('finance.invoices.show', $invoice)
            ->with('status', 'Paiement enregistré.');
    }

    public function cancel(CancelPaymentRequest $request, Payment $payment): RedirectResponse
    {
        try {
            $this->payments->cancel($payment, Auth::user(), $request->validated('reason'));
        } catch (PaymentAlreadyCancelled $e) {
            return redirect()->route('finance.invoices.show', $payment->invoice)
                ->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect()->route('finance.invoices.show', $payment->invoice)
            ->with('status', 'Paiement annulé.');
    }
}
