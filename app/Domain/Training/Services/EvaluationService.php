<?php

namespace App\Domain\Training\Services;

use App\Domain\Students\Models\Student;
use App\Domain\Training\Enums\SkillLevel;
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
                $existing?->level === SkillLevel::Acquired => $existing->validated_at,
                default => now()->toDateString(),
            };

            SkillProgress::query()->updateOrCreate(
                ['student_id' => $student->id, 'skill_id' => (int) $skillId],
                [
                    'structure_id' => $student->structure_id,
                    'instructor_id' => $instructor?->id,
                    'level' => $skillLevel->value,
                    'validated_at' => $validatedAt,
                ]
            );
        }
    }
}
