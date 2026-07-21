<?php

use App\Domain\Finance\Enums\InvoiceStatus;
use App\Domain\Finance\Enums\LedgerEntryType;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\LedgerEntry;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Regression coverage for the still-open remark in fixs.md #1: the legacy
 * admin/eleves.php edit form showed payment fields but its save handler only
 * ever touched the `eleves` table, never `paiements`. In the rewrite, Student
 * and Invoice/Payment are separate aggregates behind separate policies and
 * controllers — there is no shared form to desync in the first place.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');

    $this->student = Student::factory()->create([
        'structure_id' => $this->structure->id,
        'last_name' => 'Mabika',
        'first_name' => 'Sylvie',
    ]);

    $this->invoice = Invoice::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->student->id,
        'amount_due' => 200000,
        'amount_paid' => 0,
    ]);
});

it('records a partial payment, updates the invoice, and journals a ledger entry', function () {
    $this->actingAs($this->admin)
        ->post(route('finance.invoices.payments.store', $this->invoice), [
            'amount' => 80000,
            'method' => 'cash',
        ])
        ->assertRedirect(route('finance.invoices.show', $this->invoice));

    $this->invoice->refresh();
    expect((float) $this->invoice->amount_paid)->toBe(80000.0);
    expect($this->invoice->status)->toBe(InvoiceStatus::Partial);

    $entry = LedgerEntry::query()->where('structure_id', $this->structure->id)->sole();
    expect((float) $entry->amount)->toBe(80000.0);
    expect($entry->type)->toBe(LedgerEntryType::Income);
});

it('marks the invoice paid once payments cover the full amount', function () {
    $this->actingAs($this->admin)->post(route('finance.invoices.payments.store', $this->invoice), [
        'amount' => 200000, 'method' => 'bank',
    ]);

    expect($this->invoice->refresh()->status)->toBe(InvoiceStatus::Paid);
    expect($this->invoice->balanceDue())->toBe(0.0);
});

it('editing a student never changes any invoice or payment', function () {
    $this->actingAs($this->admin)->post(route('finance.invoices.payments.store', $this->invoice), [
        'amount' => 50000, 'method' => 'cash',
    ]);

    $before = $this->invoice->refresh()->only(['amount_paid', 'status']);

    $this->actingAs($this->admin)->put(route('students.update', $this->student), [
        'last_name' => 'Mabika',
        'first_name' => 'Sylvie-Renamed',
        'license_category' => 'B',
        'course_type' => 'normal',
    ])->assertRedirect();

    expect($this->invoice->refresh()->only(['amount_paid', 'status']))->toBe($before);
});
