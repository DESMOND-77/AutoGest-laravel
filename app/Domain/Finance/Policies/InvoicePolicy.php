<?php

namespace App\Domain\Finance\Policies;

use App\Domain\Finance\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('admin') && $invoice->structure_id === $user->structure_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function recordPayment(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('admin') && $invoice->structure_id === $user->structure_id;
    }
}
