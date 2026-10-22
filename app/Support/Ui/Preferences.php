<?php

namespace App\Support\Ui;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Préférences d'apparence : cookie `ui_prefs` (écrit par JavaScript, non chiffré)
 * doublé en base (users.ui_preferences) pour l'utilisateur connecté.
 *
 * Aucune valeur n'est jamais renvoyée telle quelle dans le HTML : tout passe
 * par les listes blanches ci-dessous (enum Theme, ACCENTS, DENSITIES, PERFS).
 */
final readonly class Preferences
{
    public const ACCENTS = ['indigo', 'violet', 'rose', 'orange', 'amber', 'emerald', 'teal', 'sky'];

    public const DENSITIES = ['comfortable', 'compact'];

    public const PERFS = ['auto', 'lite', 'full'];

    public const COOKIE = 'ui_prefs';

    public function __construct(
        public ?Theme $theme = null,      // null = « auto »
        public ?string $accent = null,
        public string $density = 'comfortable',
        public string $perf = 'auto',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $raw = self::decode($request->cookie(self::COOKIE));

        $user = $request->user();
        if ($user instanceof User && is_array($user->ui_preferences)) {
            $raw = array_merge($raw, $user->ui_preferences);
        }

        return self::fromArray($raw);
    }

    /** @param  array<string, mixed>  $raw */
    public static function fromArray(array $raw): self
    {
        return new self(
            theme: is_string($raw['theme'] ?? null) ? Theme::tryFrom($raw['theme']) : null,
            accent: in_array($raw['accent'] ?? null, self::ACCENTS, true) ? $raw['accent'] : null,
            density: ($raw['density'] ?? '') === 'compact' ? 'compact' : 'comfortable',
            perf: in_array($raw['perf'] ?? null, ['lite', 'full'], true) ? $raw['perf'] : 'auto',
        );
    }

    /** @return array<string, mixed> */
    private static function decode(mixed $cookie): array
    {
        if (! is_string($cookie) || $cookie === '') {
            return [];
        }

        $decoded = json_decode($cookie, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @return array<string, string> attributs de <html>, uniquement des valeurs autorisées */
    public function htmlAttributes(): array
    {
        $theme = $this->theme ?? Theme::Aurore;

        return array_filter([
            'data-theme' => $theme->value,
            'data-mode' => $theme->mode(),
            'data-accent' => $this->accent,
            'data-density' => $this->density,
            'data-perf' => $this->perf === 'lite' ? 'lite' : null,
            'data-perf-mode' => $this->perf,
            // Charge utile lue par le store Alpine au démarrage (valeurs validées).
            'data-ui-prefs' => $this->toJson(),
        ], fn ($value) => $value !== null);
    }

    /** Charge utile JSON consommée par le store Alpine (valeurs validées uniquement). */
    public function toArray(): array
    {
        return [
            'theme' => $this->theme?->value ?? 'auto',
            'accent' => $this->accent,
            'density' => $this->density,
            'perf' => $this->perf,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /** @return array<string, mixed> */
    public function toDatabase(): array
    {
        return $this->toArray();
    }
}
