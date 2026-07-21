<?php

namespace App\Domain\Scheduling\Database\Factories;

use App\Domain\Scheduling\Enums\PresenceStatus;
use App\Domain\Scheduling\Enums\SessionType;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonSession>
 */
class LessonSessionFactory extends Factory
{
    protected $model = LessonSession::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'student_id' => Student::factory(),
            'instructor_id' => User::factory(),
            'type' => SessionType::Practical,
            'scheduled_date' => now()->addDay()->toDateString(),
            'starts_at' => '08:00',
            'ends_at' => '09:00',
            'presence' => PresenceStatus::Planned,
        ];
    }
}
