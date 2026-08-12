<?php

namespace App\Domain\Students\Enums;

/**
 * Mirrors LifecycleStage's transition-guard pattern (see WF-03 in
 * docs/audit/business-workflow.md): a dossier moves forward through review,
 * with a single allowed back-edge when a submitted dossier is rejected and
 * sent back for correction.
 */
enum DossierStatus: string
{
    case Incomplete = 'incomplete';
    case Complete = 'complete';
    case Submitted = 'submitted';
    case Validated = 'validated';

    /**
     * @return DossierStatus[] statuses this one may transition to.
     */
    public function allowedNextStages(): array
    {
        return match ($this) {
            self::Incomplete => [self::Complete],
            self::Complete => [self::Submitted],
            self::Submitted => [self::Validated, self::Incomplete],
            self::Validated => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedNextStages(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Incomplete => 'Incomplet',
            self::Complete => 'Complet',
            self::Submitted => 'Soumis',
            self::Validated => 'Validé',
        };
    }
}
