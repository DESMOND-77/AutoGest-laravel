<?php

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

it('groups skills by category with an acquired subtotal', function () {
    $skillA = Skill::factory()->create(['structure_id' => $this->structure->id, 'category' => 'Circulation', 'label' => 'Priorités']);
    $skillB = Skill::factory()->create(['structure_id' => $this->structure->id, 'category' => 'Circulation', 'label' => 'Ronds-points']);
    $skillC = Skill::factory()->create(['structure_id' => $this->structure->id, 'category' => 'Maniabilité', 'label' => 'Créneau']);

    SkillProgress::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->student->id,
        'skill_id' => $skillA->id,
        'level' => SkillLevel::Acquired,
        'validated_at' => '2026-07-21',
    ]);

    $response = $this->actingAs($this->moniteur)->get(route('training.evaluation.show', $this->student));

    $response->assertOk()
        ->assertSeeInOrder(['Circulation', '1/2 acquises'])
        ->assertSeeInOrder(['Maniabilité', '0/1 acquises'])
        ->assertSee('Validé le 21/07/2026');
});

it('does not show a validation date for a skill that is not acquired', function () {
    $skill = Skill::factory()->create(['structure_id' => $this->structure->id, 'category' => 'Circulation']);

    $this->actingAs($this->moniteur)->get(route('training.evaluation.show', $this->student))
        ->assertOk()
        ->assertDontSee('Validé le');
});
