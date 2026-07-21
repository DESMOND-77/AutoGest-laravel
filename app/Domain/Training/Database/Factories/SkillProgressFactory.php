<?php

namespace App\Domain\Training\Database\Factories;

use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Training\Enums\SkillLevel;
use App\Domain\Training\Models\Skill;
use App\Domain\Training\Models\SkillProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkillProgress>
 */
class SkillProgressFactory extends Factory
{
    protected $model = SkillProgress::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'student_id' => Student::factory(),
            'skill_id' => Skill::factory(),
            'level' => SkillLevel::NotStarted,
        ];
    }
}
