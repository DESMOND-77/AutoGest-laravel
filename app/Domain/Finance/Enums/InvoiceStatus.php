<?php

namespace App\Domain\Finance\Enums;

enum InvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case Partial = 'partial';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Impayée',
            self::Partial => 'Partiellement réglée',
            self::Paid => 'Soldée',
        };
    }
}
