<?php

use App\Domain\Finance\Enums\LedgerEntryType;
use App\Domain\Finance\Models\LedgerEntry;
use App\Domain\Reports\Services\ReportService;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Training\Enums\ExamResult;
use App\Domain\Training\Models\Exam;
use App\Support\TenantContext;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    TenantContext::set($this->structure);
});

afterEach(fn () => TenantContext::clear());

it('aggregates income ledger entries by month and ignores expenses and other tenants', function () {
    $otherStructure = Structure::factory()->create();

    LedgerEntry::factory()->create([
        'structure_id' => $this->structure->id,
        'type' => LedgerEntryType::Income,
        'amount' => 10000,
        'occurred_on' => now()->toDateString(),
    ]);
    LedgerEntry::factory()->create([
        'structure_id' => $this->structure->id,
        'type' => LedgerEntryType::Income,
        'amount' => 5000,
        'occurred_on' => now()->toDateString(),
    ]);
    LedgerEntry::factory()->create([
        'structure_id' => $this->structure->id,
        'type' => LedgerEntryType::Expense,
        'amount' => 99999,
        'occurred_on' => now()->toDateString(),
    ]);
    LedgerEntry::factory()->create([
        'structure_id' => $otherStructure->id,
        'type' => LedgerEntryType::Income,
        'amount' => 77777,
        'occurred_on' => now()->toDateString(),
    ]);

    $result = app(ReportService::class)->revenueByMonth(3);

    expect($result)->toHaveCount(3);
    expect($result->last()['total'])->toBe(15000.0);
    expect($result->first()['total'])->toBe(0.0);
});

it('computes the exam pass rate only from decided exams', function () {
    Exam::factory()->create(['structure_id' => $this->structure->id, 'result' => ExamResult::Passed]);
    Exam::factory()->create(['structure_id' => $this->structure->id, 'result' => ExamResult::Passed]);
    Exam::factory()->create(['structure_id' => $this->structure->id, 'result' => ExamResult::Failed]);
    Exam::factory()->create(['structure_id' => $this->structure->id, 'result' => ExamResult::Pending]);

    $summary = app(ReportService::class)->examResultsSummary();

    expect($summary)->toBe(['passed' => 2, 'failed' => 1, 'pending' => 1, 'rate' => 66.7]);
});

it('groups students by lifecycle stage including empty stages at zero', function () {
    Student::factory()->create(['structure_id' => $this->structure->id]);
    Student::factory()->create(['structure_id' => $this->structure->id]);
    Student::factory()->stage(LifecycleStage::FormerStudent)->create(['structure_id' => $this->structure->id]);

    $result = app(ReportService::class)->studentsByStage();

    expect($result->get(LifecycleStage::Prospect->label()))->toBe(2);
    expect($result->get(LifecycleStage::FormerStudent->label()))->toBe(1);
    expect($result->get(LifecycleStage::TheoryCourse->label()))->toBe(0);
});
