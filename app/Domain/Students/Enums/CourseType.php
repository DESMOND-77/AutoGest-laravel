<?php

namespace App\Domain\Students\Enums;

enum CourseType: string
{
    case Normal = 'normal';
    case Accelerated = 'accelerated';
    case Accelerated10Days = 'accelerated_10d';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Accelerated => 'Accéléré',
            self::Accelerated10Days => 'Accéléré 10 jours',
        };
    }
}
