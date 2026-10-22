<?php

namespace App\Domain\Finance\Exceptions;

use App\Domain\Finance\Models\Payment;
use RuntimeException;

class PaymentAlreadyCancelled extends RuntimeException
{
    public static function for(Payment $payment): self
    {
        return new self("Le paiement #{$payment->id} est déjà annulé.");
    }
}
