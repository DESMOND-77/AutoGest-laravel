<?php

namespace App\Domain\Instructors\Enums;

enum InstructorStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'En activité',
            self::Inactive => 'Inactif',
        };
    }
}
