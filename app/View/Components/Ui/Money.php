<?php

namespace App\View\Components\Ui;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Montant en FCFA : espaces insécables, aucune décimale, chiffres tabulaires.
 * La sortie PHP doit être identique au rendu JavaScript Intl (fr-GA / XAF).
 */
class Money extends Component
{
    public function __construct(
        public int|float|string|null $amount = 0,
        public bool $signed = false,
        public ?string $currency = null,
    ) {}

    public function formatted(): string
    {
        $value = (float) ($this->amount ?? 0);
        $rounded = (int) round(abs($value));
        $sign = $value < 0 ? '−' : ($this->signed && $value > 0 ? '+' : '');

        return $sign.number_format($rounded, 0, ',', "\u{202F}")."\u{00A0}".$this->currencyLabel();
    }

    public function currencyLabel(): string
    {
        return $this->currency ?? (string) config('app.currency', 'FCFA');
    }

    public function render(): View
    {
        return view('components.ui.money');
    }
}
