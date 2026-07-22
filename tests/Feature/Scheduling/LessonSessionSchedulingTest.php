<?php

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Scheduling\Enums\PresenceStatus;
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

    $this->instructor = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->instructor->assignRole('moniteur');

    $this->student = Student::factory()->create(['structure_id' => $this->structure->id]);
});

it('rejects scheduling a session that overlaps an existing one for the same instructor', function () {
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id,
        'scheduled_date' => '2026-08-10',
        'starts_at' => '08:00',
        'ends_at' => '09:00',
    ]);

    $this->actingAs($this->admin)->post(route('scheduling.store'), [
        'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id,
        'type' => 'practical',
        'scheduled_date' => '2026-08-10',
        'starts_at' => '08:30',
        'ends_at' => '09:30',
    ])->assertSessionHasErrors('starts_at');

    expect(LessonSession::query()->count())->toBe(1);
});

it('rejects scheduling a session that reuses a vehicle already booked at that time', function () {
    $vehicle = Vehicle::factory()->create(['structure_id' => $this->structure->id]);
    $otherInstructor = User::factory()->create(['structure_id' => $this->structure->id]);
    $otherInstructor->assignRole('moniteur');

    LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->student->id,
        'instructor_id' => $otherInstructor->id,
        'vehicle_id' => $vehicle->id,
        'scheduled_date' => '2026-08-10',
        'starts_at' => '08:00',
        'ends_at' => '09:00',
    ]);

    $this->actingAs($this->admin)->post(route('scheduling.store'), [
        'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id,
        'vehicle_id' => $vehicle->id,
        'type' => 'practical',
        'scheduled_date' => '2026-08-10',
        'starts_at' => '08:30',
        'ends_at' => '09:30',
    ])->assertSessionHasErrors('starts_at');

    expect(LessonSession::query()->count())->toBe(1);
});

it('does not let a moniteur mark presence on a session that is not theirs', function () {
    $otherInstructor = User::factory()->create(['structure_id' => $this->structure->id]);
    $otherInstructor->assignRole('moniteur');

    $session = LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id,
    ]);

    $this->actingAs($otherInstructor)
        ->patch(route('scheduling.presence', $session), ['presence' => PresenceStatus::Present->value])
        ->assertForbidden();

    expect($session->fresh()->presence)->toBe(PresenceStatus::Planned);
});

it('lets the assigned moniteur mark presence', function () {
    $session = LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id,
    ]);

    $this->actingAs($this->instructor)
        ->patch(route('scheduling.presence', $session), ['presence' => PresenceStatus::Present->value])
        ->assertRedirect();

    expect($session->fresh()->presence)->toBe(PresenceStatus::Present);
});
