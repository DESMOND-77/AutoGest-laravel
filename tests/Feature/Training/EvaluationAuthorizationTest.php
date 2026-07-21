<?php

use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Training\Enums\SkillLevel;
use App\Domain\Training\Models\Skill;
use App\Domain\Training\Models\SkillProgress;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Direct regression test for fixs.md #4: the legacy moniteur/evaluation.php
 * resolved the target student from ?eleve=<id> with no ownership check at
 * all, letting a moniteur read and write evaluations for any student, in any
 * tenant. Here the route is Student-bound and gated by
 * StudentPolicy::evaluate() before the page or the save handler runs.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);

    $this->assignedMoniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->assignedMoniteur->assignRole('moniteur');

    $this->otherMoniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->otherMoniteur->assignRole('moniteur');

    $this->student = Student::factory()->create([
        'structure_id' => $this->structure->id,
        'instructor_id' => $this->assignedMoniteur->id,
    ]);

    $this->skill = Skill::factory()->create(['structure_id' => $this->structure->id]);
});

it('lets the assigned moniteur view and submit an evaluation', function () {
    $this->actingAs($this->assignedMoniteur)
        ->get(route('training.evaluation.show', $this->student))
        ->assertOk();

    $this->actingAs($this->assignedMoniteur)
        ->post(route('training.evaluation.store', $this->student), [
            'levels' => [$this->skill->id => SkillLevel::Acquired->value],
        ])
        ->assertRedirect();

    $progress = SkillProgress::query()->where('student_id', $this->student->id)->sole();
    expect($progress->level)->toBe(SkillLevel::Acquired);
    expect($progress->instructor_id)->toBe($this->assignedMoniteur->id);
});

it('blocks a moniteur who is not assigned to the student, on both read and write', function () {
    $this->actingAs($this->otherMoniteur)
        ->get(route('training.evaluation.show', $this->student))
        ->assertForbidden();

    $this->actingAs($this->otherMoniteur)
        ->post(route('training.evaluation.store', $this->student), [
            'levels' => [$this->skill->id => SkillLevel::Acquired->value],
        ])
        ->assertForbidden();

    expect(SkillProgress::query()->where('student_id', $this->student->id)->count())->toBe(0);
});

it('blocks a moniteur from another tenant entirely', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $foreignMoniteur = User::factory()->create(['structure_id' => $otherStructure->id]);
    $foreignMoniteur->assignRole('moniteur');

    $this->actingAs($foreignMoniteur)
        ->get(route('training.evaluation.show', $this->student))
        ->assertForbidden();
});
