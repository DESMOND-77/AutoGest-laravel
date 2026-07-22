<?php

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Scheduling\Services\ConflictRule;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;

/**
 * Regression coverage for fixs.md #2: the legacy planning grid matched a
 * session into a cell via substr($s['heure_debut'],0,5) === "$h:00", which
 * both had a real string-format bug AND could never represent a half-hour
 * session (only whole-hour grid rows existed at all). This rule does a real
 * time-range overlap comparison instead, so 30-minute sessions work exactly
 * like any other.
 */
beforeEach(function () {
    $structure = Structure::factory()->create();
    $this->instructor = User::factory()->create(['structure_id' => $structure->id]);
    $student = Student::factory()->create(['structure_id' => $structure->id]);

    LessonSession::factory()->create([
        'structure_id' => $structure->id,
        'student_id' => $student->id,
        'instructor_id' => $this->instructor->id,
        'scheduled_date' => '2026-08-03',
        'starts_at' => '08:00',
        'ends_at' => '08:30',
    ]);

    $this->rule = new ConflictRule;
});

it('detects a conflict for an overlapping half-hour session', function () {
    expect($this->rule->hasConflict($this->instructor->id, '2026-08-03', '08:15', '08:45'))->toBeTrue();
});

it('allows a back-to-back half-hour session with no overlap', function () {
    expect($this->rule->hasConflict($this->instructor->id, '2026-08-03', '08:30', '09:00'))->toBeFalse();
});

it('allows the same half-hour slot for a different instructor', function () {
    $otherInstructor = User::factory()->create(['structure_id' => $this->instructor->structure_id]);

    expect($this->rule->hasConflict($otherInstructor->id, '2026-08-03', '08:00', '08:30'))->toBeFalse();
});

it('rejects a range where the end is before the start', function () {
    expect($this->rule->validateRange('09:00', '08:30'))->toBeFalse();
});

it('detects a conflict for an overlapping session using the same vehicle', function () {
    $vehicle = Vehicle::factory()->create(['structure_id' => $this->instructor->structure_id]);
    $student = Student::factory()->create(['structure_id' => $this->instructor->structure_id]);

    LessonSession::factory()->create([
        'structure_id' => $this->instructor->structure_id,
        'student_id' => $student->id,
        'instructor_id' => $this->instructor->id,
        'vehicle_id' => $vehicle->id,
        'scheduled_date' => '2026-08-04',
        'starts_at' => '09:00',
        'ends_at' => '10:00',
    ]);

    expect($this->rule->hasVehicleConflict($vehicle->id, '2026-08-04', '09:30', '10:30'))->toBeTrue();
});

it('allows the same slot for a different vehicle', function () {
    $vehicle = Vehicle::factory()->create(['structure_id' => $this->instructor->structure_id]);
    $otherVehicle = Vehicle::factory()->create(['structure_id' => $this->instructor->structure_id]);
    $student = Student::factory()->create(['structure_id' => $this->instructor->structure_id]);

    LessonSession::factory()->create([
        'structure_id' => $this->instructor->structure_id,
        'student_id' => $student->id,
        'instructor_id' => $this->instructor->id,
        'vehicle_id' => $vehicle->id,
        'scheduled_date' => '2026-08-04',
        'starts_at' => '09:00',
        'ends_at' => '10:00',
    ]);

    expect($this->rule->hasVehicleConflict($otherVehicle->id, '2026-08-04', '09:00', '10:00'))->toBeFalse();
});
