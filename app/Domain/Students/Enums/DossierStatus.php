<?php

namespace App\Domain\Students\Enums;

enum DossierStatus: string
{
    case Incomplete = 'incomplete';
    case Complete = 'complete';
    case Submitted = 'submitted';
    case Validated = 'validated';

    public function label(): string
    {
        return match ($this) {
            self::Incomplete => 'Incomplet',
            self::Complete => 'Complet',
            self::Submitted => 'Soumis',
            self::Validated => 'Validé',
        };
    }
}
