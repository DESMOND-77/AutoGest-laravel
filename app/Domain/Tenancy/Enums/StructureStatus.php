<?php

namespace App\Domain\Tenancy\Enums;

enum StructureStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Deactivated = 'deactivated';

    public function allowsLogin(): bool
    {
        return $this === self::Active;
    }
}
