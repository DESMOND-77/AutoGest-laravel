<?php

namespace App\Domain\Training\Enums;

enum ExamResult: string
{
    case Pending = 'pending';
    case Passed = 'passed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Passed => 'Réussi',
            self::Failed => 'Échoué',
            self::Cancelled => 'Annulé',
        };
    }
}
