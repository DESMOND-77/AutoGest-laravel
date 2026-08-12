<?php

use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * MT-05 follow-up: Training/Quiz had no tenant isolation coverage. The
 * cross-tenant surface is QuizController::studentResults(), which binds a
 * {student} and defers to StudentPolicy — now also protected by the MT-01
 * middleware-priority fix (404 before the policy even runs).
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->schoolA = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->schoolB = Structure::factory()->create(['status' => StructureStatus::Active]);

    $this->adminB = User::factory()->create(['structure_id' => $this->schoolB->id]);
    $this->adminB->assignRole('admin');

    $this->studentA = Student::factory()->create(['structure_id' => $this->schoolA->id]);
});

it('does not let an admin of school B view quiz results of a student belonging to school A', function () {
    $this->actingAs($this->adminB)
        ->get(route('quiz.students.results', $this->studentA))
        ->assertNotFound();
});
