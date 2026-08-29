<?php

namespace App\Domain\Students\Enums;

/**
 * dossier_status is purely computed, not manually transitioned - see
 * DossierStatusService::syncFor(). Order: Incomplete -> Complete ->
 * Validated -> Submitted.
 */
enum DossierStatus: string
{
    case Incomplete = 'incomplete';
    case Complete = 'complete';
    case Validated = 'validated';
    case Submitted = 'submitted';

    public function label(): string
    {
        return match ($this) {
            self::Incomplete => 'Incomplet',
            self::Complete => 'Complet',
            self::Validated => 'Validé',
            self::Submitted => 'Soumis',
        };
    }
}
