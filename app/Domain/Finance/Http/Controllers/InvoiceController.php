<?php

namespace App\Domain\Finance\Http\Controllers;

use App\Domain\Finance\Http\Requests\StoreInvoiceRequest;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\TrainingPackage;
use App\Domain\Finance\Repositories\InvoiceRepositoryInterface;
use App\Domain\Finance\Services\InvoicingService;
use App\Domain\Students\Models\Student;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoices,
        private readonly InvoicingService $invoicing,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Invoice::class);

        return view('finance.invoices.index', [
            'invoices' => $this->invoices->paginate(),
        ]);
    }

    public function create(Student $student): View
    {
        $this->authorize('create', Invoice::class);

        return view('finance.invoices.create', [
            'student' => $student,
            'packages' => TrainingPackage::query()->where('active', true)->get(),
        ]);
    }

    public function store(StoreInvoiceRequest $request, Student $student): RedirectResponse
    {
        $invoice = $this->invoicing->createForStudent($student, $request->validated());

        return redirect()->route('finance.invoices.show', $invoice)
            ->with('status', 'Facture créée.');
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        return view('finance.invoices.show', [
            'invoice' => $invoice->load('payments.recordedBy', 'student'),
        ]);
    }
}
