<?php

use App\Domain\Finance\Models\Invoice;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->schoolA = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->schoolB = Structure::factory()->create(['status' => StructureStatus::Active]);

    $this->adminB = User::factory()->create(['structure_id' => $this->schoolB->id]);
    $this->adminB->assignRole('admin');

    $studentA = Student::factory()->create(['structure_id' => $this->schoolA->id]);
    $this->invoiceA = Invoice::factory()->create([
        'structure_id' => $this->schoolA->id,
        'student_id' => $studentA->id,
    ]);
});

it('does not let an admin of school B view an invoice belonging to school A', function () {
    $this->actingAs($this->adminB)
        ->get(route('finance.invoices.show', $this->invoiceA))
        ->assertNotFound();
});

it('does not let an admin of school B record a payment on an invoice belonging to school A', function () {
    $this->actingAs($this->adminB)
        ->post(route('finance.invoices.payments.store', $this->invoiceA), [
            'amount' => 1000,
            'method' => 'cash',
        ])
        ->assertNotFound();

    expect((float) $this->invoiceA->fresh()->amount_paid)->toBe(0.0);
});

it('does not let an admin of school B cancel a payment belonging to school A', function () {
    $adminA = User::factory()->create(['structure_id' => $this->schoolA->id]);
    $adminA->assignRole('admin');

    $this->actingAs($adminA)->post(route('finance.invoices.payments.store', $this->invoiceA), [
        'amount' => 1000,
        'method' => 'cash',
    ]);

    $payment = $this->invoiceA->payments()->sole();

    $this->actingAs($this->adminB)
        ->post(route('finance.payments.cancel', $payment))
        ->assertNotFound();

    expect((float) $this->invoiceA->fresh()->amount_paid)->toBe(1000.0);
});
