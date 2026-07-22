<?php

use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Support\TenantContext;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create();
    $this->fixturePath = base_path('tests/Fixtures/legacy-import');
});

it('imports students, invoices and payments from the legacy inscription.csv', function () {
    $this->artisan('import:legacy-students', [
        'structure' => $this->structure->id,
        'path' => $this->fixturePath,
    ])->assertSuccessful();

    TenantContext::set($this->structure);

    expect(Student::query()->count())->toBe(2);

    $coralie = Student::query()->where('first_name', 'Coralie')->first();
    expect($coralie)->not->toBeNull();
    expect($coralie->last_name)->toBe('YACKOUNDA MOUGOULA');
    expect($coralie->phone)->toBe('074123456');
    expect($coralie->registered_at->toDateString())->toBe('2026-03-15');

    $invoice = Invoice::query()->where('student_id', $coralie->id)->first();
    expect((float) $invoice->amount_due)->toBe(50000.0);

    $payment = Payment::query()->where('invoice_id', $invoice->id)->first();
    expect((float) $payment->amount)->toBe(40000.0);

    TenantContext::clear();
});

it('is idempotent: running it twice does not duplicate students', function () {
    $this->artisan('import:legacy-students', [
        'structure' => $this->structure->id,
        'path' => $this->fixturePath,
    ])->assertSuccessful();

    $this->artisan('import:legacy-students', [
        'structure' => $this->structure->id,
        'path' => $this->fixturePath,
    ])->assertSuccessful();

    TenantContext::set($this->structure);
    expect(Student::query()->count())->toBe(2);
    TenantContext::clear();
});
