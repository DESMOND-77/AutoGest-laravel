<?php

namespace App\Domain\Recyclage\Enums;

enum RecyclageMotif: string
{
    case Test = 'test';
    case Recyclage = 'recyclage';

    public function label(): string
    {
        return match ($this) {
            self::Test => 'Test',
            self::Recyclage => 'Recyclage',
        };
    }
}
