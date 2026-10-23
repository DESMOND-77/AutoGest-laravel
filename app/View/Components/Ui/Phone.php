<?php

namespace App\View\Components\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Téléphone : jamais de sur-validation côté front, simple mise en forme
 * lisible et lien tel: cliquable si activé.
 */
class Phone extends Component
{
    public function __construct(
        public ?string $value = null,
        public bool $link = true,
    ) {}

    public function display(): string
    {
        $raw = trim((string) $this->value);
        if ($raw === '') {
            return '—';
        }

        $digits = preg_replace('/[^\d+]/', '', $raw);

        // 074 12 34 56 → 074 12 34 56 ; +241 74 12 34 56 → +241 74 12 34 56
        if (str_starts_with((string) $digits, '+241')) {
            $rest = substr((string) $digits, 4);
            return '+241 '.trim(chunk_split($rest, 2, ' '));
        }

        return trim(chunk_split((string) $digits, 2, ' '));
    }

    public function href(): ?string
    {
        $raw = trim((string) $this->value);

        return $raw === '' ? null : 'tel:'.preg_replace('/[^\d+]/', '', $raw);
    }

    public function render(): View
    {
        return view('components.ui.phone');
    }
}
