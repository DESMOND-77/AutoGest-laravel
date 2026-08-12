<?php

namespace App\Domain\Finance\Policies;

use App\Domain\Finance\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function cancel(User $user, Payment $payment): bool
    {
        return $user->hasRole('admin') && $payment->structure_id === $user->structure_id;
    }
}
