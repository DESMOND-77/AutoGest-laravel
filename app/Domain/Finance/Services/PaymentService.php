<?php

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Enums\InvoiceStatus;
use App\Domain\Finance\Enums\LedgerEntryType;
use App\Domain\Finance\Events\PaymentRecorded;
use App\Domain\Finance\Exceptions\PaymentAlreadyCancelled;
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

    /**
     * Reverses a payment: the invoice's amount_paid/status are rolled back
     * and a compensating expense entry is journaled — the original Payment
     * and its LedgerEntry are kept as-is (never deleted/mutated) so the
     * financial trail stays intact. See docs/audit/business-workflow.md
     * FIN-02: there was previously no way to correct a mis-recorded payment
     * without going straight into the database.
     */
    public function cancel(Payment $payment, ?User $cancelledBy = null, ?string $reason = null): Payment
    {
        if ($payment->isCancelled()) {
            throw PaymentAlreadyCancelled::for($payment);
        }

        return DB::transaction(function () use ($payment, $cancelledBy, $reason) {
            $invoice = $payment->invoice;

            $invoice->amount_paid = bcsub((string) $invoice->amount_paid, (string) $payment->amount, 2);
            $invoice->status = $this->statusFor($invoice);
            $invoice->save();

            LedgerEntry::query()->create([
                'created_by' => $cancelledBy?->id,
                'type' => LedgerEntryType::Expense,
                'amount' => $payment->amount,
                'memo' => "Annulation du paiement #{$payment->id} — facture #{$invoice->id} — {$invoice->label}"
                    .($reason ? " ({$reason})" : ''),
                'occurred_on' => now()->toDateString(),
            ]);

            $payment->update([
                'cancelled_at' => now(),
                'cancelled_by' => $cancelledBy?->id,
                'cancellation_reason' => $reason,
            ]);

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
