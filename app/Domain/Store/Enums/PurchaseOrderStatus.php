<?php

namespace App\Domain\Store\Enums;

enum PurchaseOrderStatus: string
{
    case Pending = 'pending';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::PartiallyReceived => 'Réception partielle',
            self::Received => 'Réceptionnée',
            self::Cancelled => 'Annulée',
        };
    }
}
