<?php

namespace App\Domain\Fleet\Enums;

enum VehicleStatus: string
{
    case Active = 'active';
    case Maintenance = 'maintenance';
    case OutOfService = 'out_of_service';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'En service',
            self::Maintenance => 'En entretien',
            self::OutOfService => 'Hors service',
        };
    }
}
