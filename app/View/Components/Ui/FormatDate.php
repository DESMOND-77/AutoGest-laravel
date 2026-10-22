<?php

namespace App\View\Components\Ui;

use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\View\Component;

class FormatDate extends Component
{
    public function __construct(
        public CarbonInterface|string|null $value = null,
        public string $format = 'short',
        public bool $relative = false,
    ) {}

    public function date(): ?Carbon
    {
        if ($this->value === null || $this->value === '') {
            return null;
        }

        $date = $this->value instanceof CarbonInterface
            ? Carbon::instance($this->value->toDateTime())
            : Carbon::parse($this->value);

        return $date->locale('fr')->timezone(config('app.timezone', 'Africa/Libreville'));
    }

    public function display(): string
    {
        $date = $this->date();
        if (! $date) {
            return '—';
        }

        return match ($this->format) {
            'long' => ucfirst($date->translatedFormat('l j F Y')),
            'time' => $date->format('H:i'),
            'datetime' => $date->translatedFormat('j M Y').' à '.$date->format('H:i'),
            'month' => ucfirst($date->translatedFormat('F Y')),
            'short' => $date->translatedFormat('D j M Y'),
            default => $date->translatedFormat('D j M Y'),
        };
    }

    public function absolute(): string
    {
        $date = $this->date();

        return $date ? $date->translatedFormat('l j F Y à H:i') : '—';
    }

    public function iso(): ?string
    {
        return $this->date()?->toIso8601String();
    }

    public function render(): View
    {
        return view('components.ui.date');
    }
}
