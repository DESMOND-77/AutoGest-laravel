<?php

use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Training\Models\QuizAttempt;
use App\Domain\Training\Models\QuizOption;
use App\Domain\Training\Models\QuizQuestion;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);

    $this->studentUser = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->studentUser->assignRole('eleve');
    $this->student = Student::factory()->create([
        'structure_id' => $this->structure->id,
        'user_id' => $this->studentUser->id,
    ]);

    $this->question = QuizQuestion::factory()->create(['structure_id' => $this->structure->id]);
    $this->correctOption = QuizOption::factory()->create(['question_id' => $this->question->id, 'is_correct' => true]);
});

it('never includes is_correct in the questions JSON response', function () {
    $response = $this->actingAs($this->studentUser)->getJson(route('quiz.index'));

    $response->assertOk();
    $response->assertJsonMissingPath('0.options.0.is_correct');
    expect($response->getContent())->not->toContain('is_correct');
});

it('lets a student submit an attempt and returns the server-computed score', function () {
    $response = $this->actingAs($this->studentUser)->postJson(route('quiz.store'), [
        'answers' => [$this->question->id => $this->correctOption->id],
    ]);

    $response->assertCreated();
    $response->assertJson(['score' => 1, 'total_questions' => 1]);

    expect(QuizAttempt::query()->where('student_id', $this->student->id)->count())->toBe(1);
});

it('does not let an admin or moniteur take the quiz themselves', function () {
    $admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $admin->assignRole('admin');

    $this->actingAs($admin)->getJson(route('quiz.index'))->assertForbidden();
});

it('lets a moniteur view results only for their assigned student', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $otherStudent = Student::factory()->create(['structure_id' => $this->structure->id, 'instructor_id' => null]);
    $this->student->update(['instructor_id' => $moniteur->id]);

    $this->actingAs($moniteur)
        ->getJson(route('quiz.students.results', $this->student))
        ->assertOk();

    $this->actingAs($moniteur)
        ->getJson(route('quiz.students.results', $otherStudent))
        ->assertForbidden();
});
