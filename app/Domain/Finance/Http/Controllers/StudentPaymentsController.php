<?php

namespace App\Domain\Finance\Http\Controllers;

use App\Domain\Finance\Models\Invoice;
use App\Domain\Students\Models\Student;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Read-only invoice/payment history for the eleve themselves - the student
 * is resolved from Auth::id(), never a route parameter, so there's no id to
 * forge and no InvoicePolicy check is needed (that policy is admin-only by
 * design; this controller never reaches it).
 */
class StudentPaymentsController extends Controller
{
    public function __invoke(): View
    {
        $student = Student::query()->where('user_id', Auth::id())->first();

        $invoices = $student
            ? Invoice::query()->where('student_id', $student->id)->with('payments')->latest('issued_at')->get()
            : collect();

        $balanceDue = $invoices->sum(fn (Invoice $invoice) => $invoice->balanceDue());

        return view('eleve.paiements', [
            'student' => $student,
            'invoices' => $invoices,
            'balanceDue' => $balanceDue,
        ]);
    }
}
