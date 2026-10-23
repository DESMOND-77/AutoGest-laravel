<?php

namespace App\Domain\Students\Enums;

enum LicenseCategory: string
{
    case B = 'B';
    case A = 'A';
    case C = 'C';
    case D = 'D';
    case E = 'E';

    public function label(): string
    {
        return 'Permis '.$this->value;
    }
}
