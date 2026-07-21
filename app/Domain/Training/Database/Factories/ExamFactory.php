<?php

namespace App\Domain\Training\Database\Factories;

use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Training\Enums\ExamResult;
use App\Domain\Training\Enums\ExamType;
use App\Domain\Training\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    protected $model = Exam::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'student_id' => Student::factory(),
            'type' => ExamType::Code,
            'exam_date' => now()->addWeek()->toDateString(),
            'result' => ExamResult::Pending,
        ];
    }
}
