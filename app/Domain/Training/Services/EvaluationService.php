<?php

namespace App\Domain\Training\Services;

use App\Domain\Students\Models\Student;
use App\Domain\Training\Enums\SkillLevel;
use App\Domain\Training\Models\Skill;
use App\Domain\Training\Models\SkillProgress;
use App\Models\User;

/**
 * The legacy moniteur/evaluation.php looked up the target student with no
 * ownership check at all (see fixs.md #4) - the fix there was in the Policy
 * layer, not here. This service just does the actual upsert, one row per
 * skill, exactly once per (student, skill) pair via the unique constraint.
 *
 * validated_at is set once, on the transition INTO Acquired - not
 * overwritten on every resubmission that keeps a skill Acquired, and
 * cleared whenever a skill moves away from Acquired (a moniteur correcting
 * a premature validation).
 */
class EvaluationService
{
    /**
     * @param  array<int, string>  $levels  skill_id => SkillLevel value
     */
    public function record(Student $student, array $levels, ?User $instructor = null): void
    {
        foreach ($levels as $skillId => $level) {
            $skillLevel = SkillLevel::from($level);

            $existing = SkillProgress::query()
                ->where('student_id', $student->id)
                ->where('skill_id', (int) $skillId)
                ->first();

            $validatedAt = match (true) {
                $skillLevel !== SkillLevel::Acquired => null,
                $existing?->level === SkillLevel::Acquired => $existing->validated_at ?? now()->toDateString(),
                default => now()->toDateString(),
            };

            SkillProgress::query()->updateOrCreate(
                ['student_id' => $student->id, 'skill_id' => (int) $skillId],
                [
                    'instructor_id' => $instructor?->id,
                    'level' => $skillLevel->value,
                    'validated_at' => $validatedAt,
                ]
            );
        }
    }

    /**
     * The one reusable figure every skill-progress screen needs: how many of
     * this student's skills are Acquired, out of how many exist. The
     * evaluation and eleve-progression screens each already compute their own
     * per-category subtotals inline - this is the plain overall count neither
     * of them assembles today, extracted here so a third consumer (the
     * moniteur route sheet) doesn't re-derive the join a fourth time.
     *
     * @return array{acquired: int, total: int, percent: int}
     */
    public function acquiredSummary(Student $student): array
    {
        $total = Skill::query()->count();

        if ($total === 0) {
            return ['acquired' => 0, 'total' => 0, 'percent' => 0];
        }

        $acquired = SkillProgress::query()
            ->where('student_id', $student->id)
            ->where('level', SkillLevel::Acquired)
            ->count();

        return [
            'acquired' => $acquired,
            'total' => $total,
            'percent' => (int) round(($acquired / $total) * 100),
        ];
    }
}
