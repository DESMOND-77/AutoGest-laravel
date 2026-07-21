<?php

namespace App\Domain\Training\Enums;

enum SkillLevel: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Acquired = 'acquired';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Non travaillé',
            self::InProgress => 'En cours',
            self::Acquired => 'Acquis',
        };
    }
}
