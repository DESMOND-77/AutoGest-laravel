<?php

use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Training\Enums\ExamResult;
use App\Domain\Training\Models\Exam;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
});

it('exports the exam results CSV', function () {
    Exam::factory()->create(['structure_id' => $this->structure->id, 'result' => ExamResult::Passed]);
    Exam::factory()->create(['structure_id' => $this->structure->id, 'result' => ExamResult::Failed]);

    $response = $this->actingAs($this->admin)->get(route('reports.exams.csv'));

    $response->assertOk();
    $response->assertHeader('content-disposition', 'attachment; filename=resultats-examens.csv');

    $content = $response->streamedContent();
    expect($content)->toContain('Réussis')->toContain('Échoués');
});

it('exports the students-by-stage CSV', function () {
    Student::factory()->create(['structure_id' => $this->structure->id]);

    $response = $this->actingAs($this->admin)->get(route('reports.students-by-stage.csv'));

    $response->assertOk();
    $response->assertHeader('content-disposition', 'attachment; filename=eleves-par-etape.csv');

    $content = $response->streamedContent();
    expect($content)->toContain(LifecycleStage::Prospect->label());
});
