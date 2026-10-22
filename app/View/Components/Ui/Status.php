<?php

namespace App\View\Components\Ui;

use BackedEnum;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use UnitEnum;

/**
 * Statut métier : libellé + icône + ton issus de config/ui-status.php.
 * Jamais de couleur seule : le libellé est toujours rendu dans le HTML.
 */
class Status extends Component
{
    public string $label;

    public string $tone = 'neutral';

    public ?string $icon = 'circle';

    public function __construct(
        public mixed $value = null,
        public ?string $enum = null,
        public ?string $labelOverride = null,
        public bool $dot = false,
        public bool $compact = false,
    ) {
        [$this->label, $this->tone, $this->icon] = $this->resolve();
    }

    /** @return array{0: string, 1: string, 2: string|null} */
    private function resolve(): array
    {
        $key = null;
        $class = $this->enum;

        if ($this->value instanceof BackedEnum) {
            $class = $this->value::class;
            $key = (string) $this->value->value;
        } elseif ($this->value instanceof UnitEnum) {
            $class = $this->value::class;
            $key = $this->value->name;
        } elseif (is_string($this->value)) {
            $key = $this->value;
        }

        if ($class !== null) {
            $class = ltrim($class, '\\');
            $map = config("ui-status.enums.{$class}");
            if (is_array($map) && $key !== null && isset($map[$key])) {
                return [
                    $this->labelOverride ?? $map[$key]['label'],
                    $map[$key]['tone'] ?? 'neutral',
                    $map[$key]['icon'] ?? null,
                ];
            }
        }

        $label = $this->labelOverride
            ?? (is_object($this->value) && method_exists($this->value, 'label') ? (string) $this->value->label() : null)
            ?? (is_string($this->value) && $this->value !== '' ? ucfirst(str_replace('_', ' ', $this->value)) : '—');

        return [$label, 'neutral', $this->icon];
    }

    public function toneClasses(): string
    {
        return match ($this->tone) {
            'success' => 'bg-success-soft text-success',
            'warning' => 'bg-warning-soft text-warning',
            'danger' => 'bg-danger-soft text-danger',
            'info' => 'bg-info-soft text-info',
            'accent' => 'bg-accent-soft text-accent',
            default => 'bg-hover text-ink-2',
        };
    }

    public function dotClass(): string
    {
        return match ($this->tone) {
            'success' => 'bg-success-solid',
            'warning' => 'bg-warning-solid',
            'danger' => 'bg-danger-solid',
            'info' => 'bg-info-solid',
            'accent' => 'bg-accent',
            default => 'bg-ink-3',
        };
    }

    public function render(): View
    {
        return view('components.ui.status');
    }
}
