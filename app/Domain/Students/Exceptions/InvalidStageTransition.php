<?php

namespace App\Domain\Students\Exceptions;

use App\Domain\Students\Enums\LifecycleStage;
use RuntimeException;

class InvalidStageTransition extends RuntimeException
{
    public static function from(LifecycleStage $from, LifecycleStage $to): self
    {
        return new self(sprintf(
            'Impossible de passer de "%s" à "%s".',
            $from->label(),
            $to->label(),
        ));
    }
}
