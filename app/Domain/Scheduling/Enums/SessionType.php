<?php

namespace App\Domain\Scheduling\Enums;

enum SessionType: string
{
    case Theoretical = 'theoretical';
    case Practical = 'practical';
    case Code = 'code';
    case MockExam = 'mock_exam';

    public function label(): string
    {
        return match ($this) {
            self::Theoretical => 'Cours théorique',
            self::Practical => 'Conduite',
            self::Code => 'Code',
            self::MockExam => 'Examen blanc',
        };
    }
}
