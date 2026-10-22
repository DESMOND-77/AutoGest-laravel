<?php

use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Training\Enums\SkillLevel;
use App\Domain\Training\Models\Skill;
use App\Domain\Training\Models\SkillProgress;
use App\Domain\Training\Services\EvaluationService;
use App\Support\TenantContext;
use Carbon\Carbon;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    $this->student = Student::factory()->create(['structure_id' => $this->structure->id]);
    $this->service = new EvaluationService;
    TenantContext::set($this->structure);
});

afterEach(function () {
    TenantContext::clear();
});

it('sets validated_at when a skill first becomes acquired', function () {
    $this->skill = Skill::factory()->create(['structure_id' => $this->structure->id]);
    Carbon::setTestNow('2026-07-21 10:00:00');

    $this->service->record($this->student, [$this->skill->id => SkillLevel::Acquired->value]);

    $progress = SkillProgress::query()->where('student_id', $this->student->id)->sole();
    expect($progress->validated_at->toDateString())->toBe('2026-07-21');

    Carbon::setTestNow();
});

it('does not change validated_at when a resubmission keeps the level at acquired', function () {
    $this->skill = Skill::factory()->create(['structure_id' => $this->structure->id]);
    Carbon::setTestNow('2026-07-21 10:00:00');
    $this->service->record($this->student, [$this->skill->id => SkillLevel::Acquired->value]);

    Carbon::setTestNow('2026-08-15 10:00:00');
    $this->service->record($this->student, [$this->skill->id => SkillLevel::Acquired->value]);

    $progress = SkillProgress::query()->where('student_id', $this->student->id)->sole();
    expect($progress->validated_at->toDateString())->toBe('2026-07-21');

    Carbon::setTestNow();
});

it('clears validated_at when a skill regresses from acquired to in_progress', function () {
    $this->skill = Skill::factory()->create(['structure_id' => $this->structure->id]);
    $this->service->record($this->student, [$this->skill->id => SkillLevel::Acquired->value]);
    $this->service->record($this->student, [$this->skill->id => SkillLevel::InProgress->value]);

    $progress = SkillProgress::query()->where('student_id', $this->student->id)->sole();
    expect($progress->level)->toBe(SkillLevel::InProgress);
    expect($progress->validated_at)->toBeNull();
});

it('clears validated_at when a skill regresses from acquired to not_started', function () {
    $this->skill = Skill::factory()->create(['structure_id' => $this->structure->id]);
    $this->service->record($this->student, [$this->skill->id => SkillLevel::Acquired->value]);
    $this->service->record($this->student, [$this->skill->id => SkillLevel::NotStarted->value]);

    $progress = SkillProgress::query()->where('student_id', $this->student->id)->sole();
    expect($progress->validated_at)->toBeNull();
});

it('leaves validated_at null for a skill that has never been acquired', function () {
    $this->skill = Skill::factory()->create(['structure_id' => $this->structure->id]);
    $this->service->record($this->student, [$this->skill->id => SkillLevel::InProgress->value]);

    $progress = SkillProgress::query()->where('student_id', $this->student->id)->sole();
    expect($progress->validated_at)->toBeNull();
});

it('self-heals a null validated_at on an already-acquired row', function () {
    $this->skill = Skill::factory()->create(['structure_id' => $this->structure->id]);
    Carbon::setTestNow('2026-07-21 10:00:00');

    SkillProgress::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->student->id,
        'skill_id' => $this->skill->id,
        'level' => SkillLevel::Acquired,
        'validated_at' => null,
    ]);

    $this->service->record($this->student, [$this->skill->id => SkillLevel::Acquired->value]);

    $progress = SkillProgress::query()->where('student_id', $this->student->id)->sole();
    expect($progress->validated_at->toDateString())->toBe('2026-07-21');

    Carbon::setTestNow();
});

it('summarizes acquired skills as a count and percentage', function () {
    $skillA = Skill::factory()->create(['structure_id' => $this->structure->id]);
    $skillB = Skill::factory()->create(['structure_id' => $this->structure->id]);
    Skill::factory()->create(['structure_id' => $this->structure->id]);
    Skill::factory()->create(['structure_id' => $this->structure->id]);

    $this->service->record($this->student, [
        $skillA->id => SkillLevel::Acquired->value,
        $skillB->id => SkillLevel::InProgress->value,
    ]);

    $summary = $this->service->acquiredSummary($this->student);

    expect($summary['acquired'])->toBe(1);
    expect($summary['total'])->toBe(4);
    expect($summary['percent'])->toBe(25);
});

it('summarizes zero skills as a zero percent, not a division error', function () {
    $summary = $this->service->acquiredSummary($this->student);

    expect($summary)->toBe(['acquired' => 0, 'total' => 0, 'percent' => 0]);
});
