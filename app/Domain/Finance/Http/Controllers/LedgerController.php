<?php

namespace App\Domain\Finance\Http\Controllers;

use App\Domain\Finance\Http\Requests\StoreLedgerEntryRequest;
use App\Domain\Finance\Models\LedgerEntry;
use App\Domain\Finance\Services\LedgerService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LedgerController extends Controller
{
    public function __construct(
        private readonly LedgerService $ledger,
    ) {}

    public function index(): View
    {
        $entries = LedgerEntry::query()
            ->with('payment.invoice.student', 'createdBy')
            ->latest('occurred_on')
            ->paginate(30);

        return view('finance.ledger.index', [
            'entries' => $entries,
            'balance' => $this->balance(),
        ]);
    }

    public function store(StoreLedgerEntryRequest $request): RedirectResponse
    {
        $this->ledger->recordManual($request->validated(), Auth::user());

        return redirect()->route('finance.ledger.index')->with('status', 'Écriture enregistrée.');
    }

    private function balance(): float
    {
        return (float) LedgerEntry::query()->get()->sum(
            fn (LedgerEntry $entry) => $entry->type->isCredit() ? (float) $entry->amount : -(float) $entry->amount
        );
    }
}
