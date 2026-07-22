<?php

namespace App\Domain\Scheduling\Exceptions;

use RuntimeException;

class SchedulingConflict extends RuntimeException
{
    public static function instructorBusy(): self
    {
        return new self('Ce moniteur a déjà une séance sur ce créneau.');
    }

    public static function vehicleBusy(): self
    {
        return new self('Ce véhicule est déjà réservé sur ce créneau.');
    }

    public static function invalidRange(): self
    {
        return new self("L'heure de fin doit être après l'heure de début.");
    }
}
