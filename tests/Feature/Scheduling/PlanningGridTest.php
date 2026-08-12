<?php

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Covers the planning grid rebuild (see docs/audit/ux-audit.md / legacy
 * parity notes): filters, the empty state, and that every session in the
 * response actually renders regardless of what time it falls at.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');

    $this->instructorA = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->instructorA->assignRole('moniteur');
    $this->instructorB = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->instructorB->assignRole('moniteur');

    $this->vehicle = Vehicle::factory()->create(['structure_id' => $this->structure->id]);

    $this->studentA = Student::factory()->create(['structure_id' => $this->structure->id, 'last_name' => 'Alpha']);
    $this->studentB = Student::factory()->create(['structure_id' => $this->structure->id, 'last_name' => 'Beta']);

    $this->monday = now()->startOfWeek();
});

it('shows the empty state when there are no sessions this week', function () {
    $this->actingAs($this->admin)
        ->get(route('scheduling.index', ['week' => $this->monday->toDateString()]))
        ->assertOk()
        ->assertSee('Aucune séance planifiée cette semaine.');
});

it('filters the planning by instructor', function () {
    $sessionA = LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->studentA->id,
        'instructor_id' => $this->instructorA->id,
        'scheduled_date' => $this->monday->toDateString(),
    ]);
    $sessionB = LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->studentB->id,
        'instructor_id' => $this->instructorB->id,
        'scheduled_date' => $this->monday->toDateString(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('scheduling.index', [
        'week' => $this->monday->toDateString(),
        'instructor_id' => $this->instructorA->id,
    ]));

    $response->assertOk();
    $ids = $response->viewData('sessions')->pluck('id');
    expect($ids)->toContain($sessionA->id);
    expect($ids)->not->toContain($sessionB->id);
});

it('filters the planning by vehicle', function () {
    $sessionWithVehicle = LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->studentA->id,
        'instructor_id' => $this->instructorA->id,
        'vehicle_id' => $this->vehicle->id,
        'scheduled_date' => $this->monday->toDateString(),
    ]);
    $sessionWithoutVehicle = LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->studentB->id,
        'instructor_id' => $this->instructorB->id,
        'vehicle_id' => null,
        'scheduled_date' => $this->monday->toDateString(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('scheduling.index', [
        'week' => $this->monday->toDateString(),
        'vehicle_id' => $this->vehicle->id,
    ]));

    $ids = $response->viewData('sessions')->pluck('id');
    expect($ids)->toContain($sessionWithVehicle->id);
    expect($ids)->not->toContain($sessionWithoutVehicle->id);
});

it('filters the planning by student', function () {
    $sessionA = LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->studentA->id,
        'instructor_id' => $this->instructorA->id,
        'scheduled_date' => $this->monday->toDateString(),
    ]);
    $sessionB = LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->studentB->id,
        'instructor_id' => $this->instructorB->id,
        'scheduled_date' => $this->monday->toDateString(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('scheduling.index', [
        'week' => $this->monday->toDateString(),
        'student_id' => $this->studentA->id,
    ]));

    $ids = $response->viewData('sessions')->pluck('id');
    expect($ids)->toContain($sessionA->id);
    expect($ids)->not->toContain($sessionB->id);
});

it('renders sessions falling outside the default 07:00-19:00 window', function () {
    $lateSession = LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->studentA->id,
        'instructor_id' => $this->instructorA->id,
        'scheduled_date' => $this->monday->toDateString(),
        'starts_at' => '20:00',
        'ends_at' => '21:00',
    ]);

    $this->actingAs($this->admin)
        ->get(route('scheduling.index', ['week' => $this->monday->toDateString()]))
        ->assertOk()
        ->assertSee($lateSession->student->fullName());
});

it('renders the presence control and cancel action for an admin', function () {
    $session = LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->studentA->id,
        'instructor_id' => $this->instructorA->id,
        'scheduled_date' => $this->monday->toDateString(),
    ]);

    $this->actingAs($this->admin)
        ->get(route('scheduling.index', ['week' => $this->monday->toDateString()]))
        ->assertOk()
        ->assertSee(route('scheduling.presence', $session), false)
        ->assertSee(route('scheduling.destroy', $session), false);
});

it('exports the filtered week as a CSV', function () {
    $sessionA = LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->studentA->id,
        'instructor_id' => $this->instructorA->id,
        'scheduled_date' => $this->monday->toDateString(),
        'starts_at' => '08:00',
        'ends_at' => '09:00',
    ]);
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->studentB->id,
        'instructor_id' => $this->instructorB->id,
        'scheduled_date' => $this->monday->toDateString(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('scheduling.export.csv', [
        'week' => $this->monday->toDateString(),
        'instructor_id' => $this->instructorA->id,
    ]));

    $response->assertOk();
    $response->assertHeader('content-disposition');

    $content = $response->streamedContent();
    expect($content)->toContain('Date,Début,Fin,Élève,Moniteur,Véhicule,Type,Présence');
    expect($content)->toContain($sessionA->student->fullName());
    expect($content)->toContain('08:00,09:00');
    expect($content)->not->toContain($this->studentB->fullName());
});
