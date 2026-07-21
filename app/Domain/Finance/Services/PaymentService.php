<?php

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Enums\InvoiceStatus;
use App\Domain\Finance\Enums\LedgerEntryType;
use App\Domain\Finance\Events\PaymentRecorded;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\LedgerEntry;
use App\Domain\Finance\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * The single place a payment is ever recorded. Every write here happens in
 * one transaction: the Payment row, the Invoice's running amount_paid/status,
 * and the caisse/banque LedgerEntry, together — instead of the legacy app's
 * pattern of updating paiements by hand in whichever page happened to touch
 * it (see fixs.md #1: eleves.php showed payment fields on the student edit
 * form but its save handler never wrote to the paiements table at all).
 * Editing a Student never goes anywhere near this class.
 */
class PaymentService
{
    public function record(Invoice $invoice, array $data, ?User $recordedBy = null): Payment
    {
        return DB::transaction(function () use ($invoice, $data, $recordedBy) {
            $payment = Payment::query()->create([
                'invoice_id' => $invoice->id,
                'recorded_by' => $recordedBy?->id,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'paid_at' => $data['paid_at'] ?? now()->toDateString(),
            ]);

            $invoice->amount_paid = bcadd((string) $invoice->amount_paid, (string) $data['amount'], 2);
            $invoice->status = $this->statusFor($invoice);
            $invoice->save();

            LedgerEntry::query()->create([
                'payment_id' => $payment->id,
                'created_by' => $recordedBy?->id,
                'type' => LedgerEntryType::Income,
                'amount' => $data['amount'],
                'memo' => "Paiement facture #{$invoice->id} — {$invoice->label}",
                'occurred_on' => $payment->paid_at,
            ]);

            Event::dispatch(new PaymentRecorded($payment));

            return $payment;
        });
    }

    private function statusFor(Invoice $invoice): InvoiceStatus
    {
        return match (true) {
            bccomp((string) $invoice->amount_paid, (string) $invoice->amount_due, 2) >= 0 => InvoiceStatus::Paid,
            (float) $invoice->amount_paid > 0 => InvoiceStatus::Partial,
            default => InvoiceStatus::Unpaid,
        };
    }
}
