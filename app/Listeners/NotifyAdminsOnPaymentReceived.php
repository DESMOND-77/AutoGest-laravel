<?php

namespace App\Listeners;

use App\Domain\Finance\Events\PaymentRecorded;
use App\Domain\Notifications\Notifications\AlertNotification;
use App\Models\User;

class NotifyAdminsOnPaymentReceived
{
    public function handle(PaymentRecorded $event): void
    {
        $payment = $event->payment;
        $invoice = $payment->invoice;

        $notification = new AlertNotification(
            title: 'Paiement reçu',
            message: number_format((float) $payment->amount, 0, ',', ' ')." FCFA reçus pour la facture #{$invoice->id}",
            link: route('finance.invoices.show', $invoice, absolute: false),
        );

        User::role('admin')
            ->where('structure_id', $payment->structure_id)
            ->get()
            ->each(fn (User $admin) => $admin->notify($notification));
    }
}
