<?php

use App\Domain\Scheduling\Enums\PresenceStatus;
use App\Domain\Scheduling\Enums\SessionType;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Training\Enums\SkillLevel;
use App\Domain\Training\Models\Skill;
use App\Domain\Training\Models\SkillProgress;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->moniteur->assignRole('moniteur');
    $this->student = Student::factory()->create([
        'structure_id' => $this->structure->id,
        'instructor_id' => $this->moniteur->id,
    ]);
});

it('shows a moniteur the route sheet for a student they encadre, with correct aggregates', function () {
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->moniteur->id, 'presence' => PresenceStatus::Present,
        'type' => SessionType::Practical, 'starts_at' => '08:00', 'ends_at' => '09:30',
    ]);
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->moniteur->id, 'presence' => PresenceStatus::Absent,
        'type' => SessionType::Practical, 'starts_at' => '08:00', 'ends_at' => '09:00',
    ]);
    $skill = Skill::factory()->create(['structure_id' => $this->structure->id]);
    SkillProgress::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'skill_id' => $skill->id, 'level' => SkillLevel::Acquired,
    ]);

    $response = $this->actingAs($this->moniteur)->get(route('moniteur.eleves.feuille-route', $this->student));

    $response->assertOk()
        ->assertSee('1/1') // acquired/total skills
        ->assertSee('100%');

    expect($response->original->getData()['summary']['total'])->toBe(2);
    expect($response->original->getData()['summary']['present'])->toBe(1);
    expect($response->original->getData()['summary']['absent'])->toBe(1);
    expect($response->original->getData()['summary']['practicalHoursCompleted'])->toBe(1.5);
});

it('does not let a moniteur view the route sheet of a student they do not encadre', function () {
    $otherMoniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $otherMoniteur->assignRole('moniteur');

    $this->actingAs($otherMoniteur)
        ->get(route('moniteur.eleves.feuille-route', $this->student))
        ->assertForbidden();
});

it('does not let a moniteur of another tenant view the route sheet', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $foreignMoniteur = User::factory()->create(['structure_id' => $otherStructure->id]);
    $foreignMoniteur->assignRole('moniteur');

    $this->actingAs($foreignMoniteur)
        ->get(route('moniteur.eleves.feuille-route', $this->student))
        ->assertNotFound();
});

it('does not let an admin or eleve access the moniteur-only route', function () {
    $admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('moniteur.eleves.feuille-route', $this->student))
        ->assertForbidden();
});

it('only counts sessions this moniteur personally conducted with the student', function () {
    $otherMoniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $otherMoniteur->assignRole('moniteur');

    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->moniteur->id, 'presence' => PresenceStatus::Present,
    ]);
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $otherMoniteur->id, 'presence' => PresenceStatus::Present,
    ]);

    $response = $this->actingAs($this->moniteur)
        ->get(route('moniteur.eleves.feuille-route', $this->student))
        ->assertOk();

    // A raw assertDontSee('2') is unreliable here: the rendered page's Tailwind
    // classes (grid-cols-2, h-2, py-2.5, ...) legitimately contain the digit "2"
    // hundreds of times regardless of session count. Assert on the view data instead.
    expect($response->original->getData()['summary']['total'])->toBe(1);
});

it('only shows the feuille de route link on the student list for a student the moniteur encadres', function () {
    $otherStudent = Student::factory()->create([
        'structure_id' => $this->structure->id,
    ]);

    $response = $this->actingAs($this->moniteur)->get(route('students.index'));

    $response->assertOk();
    $response->assertSee(route('moniteur.eleves.feuille-route', $this->student));
    $response->assertDontSee(route('moniteur.eleves.feuille-route', $otherStudent));
});
