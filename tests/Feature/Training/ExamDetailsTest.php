<?php

use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Training\Enums\ExamResult;
use App\Domain\Training\Models\Exam;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
});

it('lets an admin create an exam with location and inspector', function () {
    $student = Student::factory()->create(['structure_id' => $this->structure->id]);

    $this->actingAs($this->admin)->post(route('training.exams.store'), [
        'student_id' => $student->id,
        'type' => 'code',
        'exam_date' => now()->addWeek()->toDateString(),
        'location' => 'Centre d\'examen de Libreville',
        'inspector' => 'M. Ondo',
    ])->assertRedirect();

    $exam = Exam::query()->where('student_id', $student->id)->firstOrFail();
    expect($exam->location)->toBe('Centre d\'examen de Libreville');
    expect($exam->inspector)->toBe('M. Ondo');
});

it('validates fault_count and comment on exam creation', function () {
    $student = Student::factory()->create(['structure_id' => $this->structure->id]);

    $this->actingAs($this->admin)->post(route('training.exams.store'), [
        'student_id' => $student->id,
        'type' => 'code',
        'exam_date' => now()->addWeek()->toDateString(),
        'fault_count' => -1,
        'comment' => str_repeat('x', 1001),
    ])->assertSessionHasErrors(['fault_count', 'comment']);
});

it('lets an admin record fault_count and comment when setting the result', function () {
    $student = Student::factory()->create(['structure_id' => $this->structure->id]);
    $exam = Exam::factory()->create(['structure_id' => $this->structure->id, 'student_id' => $student->id]);

    $this->actingAs($this->admin)->patch(route('training.exams.update', $exam), [
        'result' => ExamResult::Passed->value,
        'fault_count' => 2,
        'comment' => 'Léger stop toléré.',
    ])->assertRedirect();

    $exam->refresh();
    expect($exam->result)->toBe(ExamResult::Passed);
    expect($exam->fault_count)->toBe(2);
    expect($exam->comment)->toBe('Léger stop toléré.');
});

it('shows location, inspector, fault count and comment on the exams screen', function () {
    $student = Student::factory()->create(['structure_id' => $this->structure->id]);
    $exam = Exam::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $student->id,
        'location' => 'Centre d\'examen de Libreville',
        'inspector' => 'M. Ondo',
        'fault_count' => 3,
        'comment' => 'Créneau raté.',
    ]);

    $this->actingAs($this->admin)->get(route('training.exams.index'))
        ->assertOk()
        ->assertSee('Centre d\'examen de Libreville')
        ->assertSee('M. Ondo')
        ->assertSee('3')
        ->assertSee('Créneau raté.');
});
