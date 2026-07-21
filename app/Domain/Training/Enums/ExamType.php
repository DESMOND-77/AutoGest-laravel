<?php

namespace App\Domain\Training\Enums;

enum ExamType: string
{
    case Code = 'code';
    case Driving = 'driving';

    public function label(): string
    {
        return match ($this) {
            self::Code => 'Code',
            self::Driving => 'Conduite',
        };
    }
}
