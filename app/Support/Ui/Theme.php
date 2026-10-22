<?php

namespace App\Support\Ui;

enum Theme: string
{
    case Aurore = 'aurore';
    case Ocean = 'ocean';
    case Sable = 'sable';
    case Foret = 'foret';
    case Crepuscule = 'crepuscule';
    case Graphite = 'graphite';

    public function mode(): string
    {
        return match ($this) {
            self::Foret, self::Crepuscule, self::Graphite => 'dark',
            default => 'light',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Aurore => 'Aurore',
            self::Ocean => 'Océan',
            self::Sable => 'Sable',
            self::Foret => 'Forêt',
            self::Crepuscule => 'Crépuscule',
            self::Graphite => 'Graphite',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Aurore => 'Lavande, rose poudré, menthe',
            self::Ocean => 'Bleus glacés et cyan',
            self::Sable => 'Crème chaude, ambre, corail',
            self::Foret => 'Vert profond et équatorial',
            self::Crepuscule => 'Indigo nuit et violet',
            self::Graphite => 'Neutre quasi noir, contraste maximal',
        };
    }

    public function defaultAccent(): string
    {
        return match ($this) {
            self::Aurore => 'indigo',
            self::Ocean => 'sky',
            self::Sable => 'orange',
            self::Foret => 'emerald',
            self::Crepuscule => 'violet',
            self::Graphite => 'sky',
        };
    }

    /** @return array<int, self> */
    public static function all(): array
    {
        return self::cases();
    }
}
