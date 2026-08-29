<?php

namespace App\Listeners;

use App\Domain\Finance\Events\PaymentRecorded;
use App\Domain\Notifications\Notifications\SmsNotification;
use Illuminate\Support\Facades\Notification;

/**
 * On-demand notification (Notification::route(...)), not $student->notify():
 * Student isn't a Notifiable model - many students have no user account at
 * all, only a phone number on file.
 */
class NotifyStudentSmsOnPaymentReceived
{
    public function handle(PaymentRecorded $event): void
    {
        $student = $event->payment->invoice->student;

        // A boutique walk-in sale produces an Invoice with no student at all
        // (invoices.student_id is nullable since the Store module) - there is
        // nobody to text, so bail out before dereferencing null.
        if (! $student || ! $student->phone) {
            return;
        }

        $amount = number_format((float) $event->payment->amount, 0, ',', ' ');

        Notification::route('sms', $student->phone)->notify(
            new SmsNotification("Auto-école : paiement de {$amount} FCFA bien reçu. Merci.")
        );
    }
}
