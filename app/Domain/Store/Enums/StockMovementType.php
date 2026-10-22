<?php

namespace App\Domain\Store\Enums;

enum StockMovementType: string
{
    case Sale = 'sale';
    case Reception = 'reception';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Vente',
            self::Reception => 'Réception',
            self::Adjustment => 'Ajustement',
        };
    }
}
