<?php

namespace App\Domain\Finance\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case AirtelMoney = 'airtel_money';
    case MoovMoney = 'moov_money';
    case Bank = 'bank';
    case Cheque = 'cheque';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Espèces',
            self::AirtelMoney => 'Airtel Money',
            self::MoovMoney => 'Moov Money',
            self::Bank => 'Virement bancaire',
            self::Cheque => 'Chèque',
        };
    }
}
