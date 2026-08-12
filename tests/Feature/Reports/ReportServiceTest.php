<?php

use App\Domain\Finance\Enums\LedgerEntryType;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\LedgerEntry;
use App\Domain\Finance\Models\Payment;
use App\Domain\Fleet\Enums\VehicleStatus;
use App\Domain\Fleet\Models\Vehicle;
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

it('lists recent payments, most recent first, excluding cancelled ones', function () {
    $student = Student::factory()->create(['structure_id' => $this->structure->id]);
    $invoice = Invoice::factory()->create(['structure_id' => $this->structure->id, 'student_id' => $student->id]);

    Payment::factory()->create(['structure_id' => $this->structure->id, 'invoice_id' => $invoice->id, 'paid_at' => now()->subDays(2)]);
    $latest = Payment::factory()->create(['structure_id' => $this->structure->id, 'invoice_id' => $invoice->id, 'paid_at' => now()]);
    Payment::factory()->create([
        'structure_id' => $this->structure->id,
        'invoice_id' => $invoice->id,
        'paid_at' => now()->subDay(),
        'cancelled_at' => now(),
    ]);

    $result = app(ReportService::class)->recentPayments();

    expect($result)->toHaveCount(2);
    expect($result->first()->id)->toBe($latest->id);
});

it('lists upcoming exams still pending a result, soonest first', function () {
    $student = Student::factory()->create(['structure_id' => $this->structure->id]);

    Exam::factory()->create(['structure_id' => $this->structure->id, 'student_id' => $student->id, 'exam_date' => now()->addDays(5), 'result' => ExamResult::Pending]);
    $soonest = Exam::factory()->create(['structure_id' => $this->structure->id, 'student_id' => $student->id, 'exam_date' => now()->addDay(), 'result' => ExamResult::Pending]);
    Exam::factory()->create(['structure_id' => $this->structure->id, 'student_id' => $student->id, 'exam_date' => now()->addDays(2), 'result' => ExamResult::Passed]);
    Exam::factory()->create(['structure_id' => $this->structure->id, 'student_id' => $student->id, 'exam_date' => now()->subDay(), 'result' => ExamResult::Pending]);

    $result = app(ReportService::class)->upcomingExams();

    expect($result)->toHaveCount(2);
    expect($result->first()->id)->toBe($soonest->id);
});

it('counts vehicles by status including statuses with no vehicles', function () {
    Vehicle::factory()->create(['structure_id' => $this->structure->id, 'status' => VehicleStatus::Active]);
    Vehicle::factory()->create(['structure_id' => $this->structure->id, 'status' => VehicleStatus::Active]);
    Vehicle::factory()->create(['structure_id' => $this->structure->id, 'status' => VehicleStatus::Maintenance]);

    $result = app(ReportService::class)->vehicleStatusCounts();

    expect($result[VehicleStatus::Active->label()])->toBe(2);
    expect($result[VehicleStatus::Maintenance->label()])->toBe(1);
    expect($result[VehicleStatus::OutOfService->label()])->toBe(0);
});
