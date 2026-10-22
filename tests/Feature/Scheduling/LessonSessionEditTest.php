<?php

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');

    $this->instructorA = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->instructorA->assignRole('moniteur');
    $this->instructorB = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->instructorB->assignRole('moniteur');

    $this->student = Student::factory()->create(['structure_id' => $this->structure->id]);
});

it('lets an admin change the instructor, vehicle and time of an existing session', function () {
    $vehicle = Vehicle::factory()->create(['structure_id' => $this->structure->id]);
    $session = LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->student->id,
        'instructor_id' => $this->instructorA->id,
        'type' => 'theoretical',
        'scheduled_date' => '2026-08-10',
        'starts_at' => '08:00',
        'ends_at' => '09:00',
    ]);

    $this->actingAs($this->admin)->patch(route('scheduling.update', $session), [
        'instructor_id' => $this->instructorB->id,
        'vehicle_id' => $vehicle->id,
        'type' => 'practical',
        'scheduled_date' => '2026-08-11',
        'starts_at' => '10:00',
        'ends_at' => '11:00',
    ])->assertSessionHasNoErrors();

    $session->refresh();
    expect($session->instructor_id)->toBe($this->instructorB->id);
    expect($session->vehicle_id)->toBe($vehicle->id);
    expect($session->scheduled_date->toDateString())->toBe('2026-08-11');
});

it('rejects an update that would create a scheduling conflict', function () {
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->student->id,
        'instructor_id' => $this->instructorB->id,
        'scheduled_date' => '2026-08-10',
        'starts_at' => '08:00',
        'ends_at' => '09:00',
    ]);

    $session = LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->student->id,
        'instructor_id' => $this->instructorA->id,
        'type' => 'theoretical',
        'scheduled_date' => '2026-08-10',
        'starts_at' => '14:00',
        'ends_at' => '15:00',
    ]);

    $this->actingAs($this->admin)->patch(route('scheduling.update', $session), [
        'instructor_id' => $this->instructorB->id,
        'type' => 'theoretical',
        'scheduled_date' => '2026-08-10',
        'starts_at' => '08:30',
        'ends_at' => '09:30',
    ])->assertSessionHasErrors('starts_at');

    expect($session->fresh()->instructor_id)->toBe($this->instructorA->id);
});

it('duplicates a session onto a new date and time', function () {
    $session = LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->student->id,
        'instructor_id' => $this->instructorA->id,
        'type' => 'theoretical',
        'scheduled_date' => '2026-08-10',
        'starts_at' => '08:00',
        'ends_at' => '09:00',
    ]);

    $this->actingAs($this->admin)->post(route('scheduling.duplicate', $session), [
        'scheduled_date' => '2026-08-17',
        'starts_at' => '08:00',
        'ends_at' => '09:00',
    ])->assertSessionHasNoErrors();

    expect(LessonSession::query()->where('student_id', $this->student->id)->count())->toBe(2);
    expect(LessonSession::query()->where('scheduled_date', '2026-08-17')->exists())->toBeTrue();
});

it('rejects a duplicate that would conflict with an existing session', function () {
    $session = LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->student->id,
        'instructor_id' => $this->instructorA->id,
        'type' => 'theoretical',
        'scheduled_date' => '2026-08-10',
        'starts_at' => '08:00',
        'ends_at' => '09:00',
    ]);

    LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->student->id,
        'instructor_id' => $this->instructorA->id,
        'scheduled_date' => '2026-08-17',
        'starts_at' => '08:00',
        'ends_at' => '09:00',
    ]);

    $this->actingAs($this->admin)->post(route('scheduling.duplicate', $session), [
        'scheduled_date' => '2026-08-17',
        'starts_at' => '08:30',
        'ends_at' => '09:30',
    ])->assertSessionHasErrors('duplicate');

    expect(LessonSession::query()->count())->toBe(2);
});
