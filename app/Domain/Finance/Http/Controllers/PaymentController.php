<?php

namespace App\Domain\Finance\Http\Controllers;

use App\Domain\Finance\Http\Requests\StorePaymentRequest;
use App\Domain\Finance\Models\Invoice;
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
}
