<?php

namespace App\Domain\Scheduling\Enums;

enum PresenceStatus: string
{
    case Planned = 'planned';
    case Present = 'present';
    case Absent = 'absent';
    case Cancelled = 'cancelled';
    case Rescheduled = 'rescheduled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planifiée',
            self::Present => 'Présent',
            self::Absent => 'Absent',
            self::Cancelled => 'Annulée',
            self::Rescheduled => 'Reportée',
        };
    }
}
