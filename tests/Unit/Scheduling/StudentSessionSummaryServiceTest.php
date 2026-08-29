<?php

use App\Domain\Scheduling\Enums\PresenceStatus;
use App\Domain\Scheduling\Enums\SessionType;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Scheduling\Services\StudentSessionSummaryService;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    $this->instructor = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->otherInstructor = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->student = Student::factory()->create(['structure_id' => $this->structure->id]);
    $this->service = new StudentSessionSummaryService;
});

it('counts total, present, and absent sessions for the given instructor only', function () {
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id, 'presence' => PresenceStatus::Present,
        'type' => SessionType::Practical, 'starts_at' => '08:00', 'ends_at' => '09:00',
    ]);
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id, 'presence' => PresenceStatus::Absent,
        'type' => SessionType::Practical, 'starts_at' => '08:00', 'ends_at' => '09:30',
    ]);
    // Belongs to a different instructor - must not be counted.
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->otherInstructor->id, 'presence' => PresenceStatus::Present,
    ]);

    $summary = $this->service->summarize($this->student, $this->instructor);

    expect($summary['total'])->toBe(2);
    expect($summary['present'])->toBe(1);
    expect($summary['absent'])->toBe(1);
});

it('sums completed practical hours from present practical sessions only', function () {
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id, 'presence' => PresenceStatus::Present,
        'type' => SessionType::Practical, 'starts_at' => '08:00', 'ends_at' => '09:00',
    ]);
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id, 'presence' => PresenceStatus::Present,
        'type' => SessionType::Practical, 'starts_at' => '10:00', 'ends_at' => '11:30',
    ]);
    // Present but theoretical - must not count toward driving hours.
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id, 'presence' => PresenceStatus::Present,
        'type' => SessionType::Theoretical, 'starts_at' => '08:00', 'ends_at' => '10:00',
    ]);
    // Practical but not present - must not count.
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id, 'presence' => PresenceStatus::Planned,
        'type' => SessionType::Practical, 'starts_at' => '08:00', 'ends_at' => '09:00',
    ]);

    $summary = $this->service->summarize($this->student, $this->instructor);

    expect($summary['practicalHoursCompleted'])->toBe(2.5);
});

it('returns the sessions ordered by most recent scheduled date first', function () {
    $older = LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id, 'scheduled_date' => now()->subDays(5)->toDateString(),
    ]);
    $newer = LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id, 'scheduled_date' => now()->subDay()->toDateString(),
    ]);

    $summary = $this->service->summarize($this->student, $this->instructor);

    expect($summary['sessions']->first()->id)->toBe($newer->id);
    expect($summary['sessions']->last()->id)->toBe($older->id);
});
