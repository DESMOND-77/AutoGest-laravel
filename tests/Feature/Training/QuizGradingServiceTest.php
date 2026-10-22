<?php

use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Training\Models\QuizOption;
use App\Domain\Training\Models\QuizQuestion;
use App\Domain\Training\Services\QuizGradingService;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    $this->student = Student::factory()->create(['structure_id' => $this->structure->id]);

    $this->question = QuizQuestion::factory()->create(['structure_id' => $this->structure->id]);
    $this->correctOption = QuizOption::factory()->create(['question_id' => $this->question->id, 'is_correct' => true]);
    $this->wrongOption = QuizOption::factory()->create(['question_id' => $this->question->id, 'is_correct' => false]);

    $this->grading = new QuizGradingService;
});

it('scores an attempt by looking up correctness server-side', function () {
    $attempt = $this->grading->grade($this->student, [$this->question->id => $this->correctOption->id]);

    expect($attempt->score)->toBe(1);
    expect($attempt->total_questions)->toBe(1);
});

it('does not award a point for a wrong option', function () {
    $attempt = $this->grading->grade($this->student, [$this->question->id => $this->wrongOption->id]);

    expect($attempt->score)->toBe(0);
});

it('cannot be tricked into a higher score by anything other than which option id was chosen', function () {
    // The service only ever receives [question_id => option_id] - there is no
    // parameter through which a caller could pass a fabricated "is_correct"
    // flag, so submitting the wrong option id can never score a point no
    // matter what else is (not) passed in.
    $attempt = $this->grading->grade($this->student, [$this->question->id => $this->wrongOption->id]);

    expect($attempt->score)->toBe(0);
    expect(QuizOption::query()->find($this->wrongOption->id)->is_correct)->toBeFalse();
});
