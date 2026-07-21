<?php

namespace App\Domain\CRM\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Converted = 'converted';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouveau',
            self::Contacted => 'Contacté',
            self::Qualified => 'Qualifié',
            self::Converted => 'Converti',
            self::Lost => 'Perdu',
        };
    }
}
