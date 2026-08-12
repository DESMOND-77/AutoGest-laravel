<?php

namespace App\Domain\Students\Exceptions;

use App\Domain\Students\Enums\DossierStatus;
use RuntimeException;

class InvalidDossierTransition extends RuntimeException
{
    public static function from(DossierStatus $from, DossierStatus $to): self
    {
        return new self(sprintf(
            'Impossible de passer le dossier de "%s" à "%s".',
            $from->label(),
            $to->label(),
        ));
    }
}
