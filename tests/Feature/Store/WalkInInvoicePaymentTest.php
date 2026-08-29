<?php

use App\Domain\Finance\Enums\PaymentMethod;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Services\PaymentService;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Services\OrderService;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    TenantContext::set($this->structure);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
});

afterEach(fn () => TenantContext::clear());

/**
 * A walk-in boutique sale produces an Invoice with no student at all. Every
 * pre-existing Finance consumer that dereferenced Invoice::student must cope.
 */
it('records a payment on a walk-in invoice and still renders every Finance screen', function () {
    $product = Product::factory()->create([
        'structure_id' => $this->structure->id,
        'price' => 2000,
        'stock_quantity' => 10,
    ]);

    $order = app(OrderService::class)
        ->place([['product_id' => $product->id, 'quantity' => 2]], null, null)['order'];

    $invoice = Invoice::query()->findOrFail($order->invoice_id);
    expect($invoice->student_id)->toBeNull();

    // Dispatches PaymentRecorded synchronously inside its transaction: the SMS
    // listener used to fatal on the null student and roll the payment back.
    $payment = app(PaymentService::class)->record($invoice, [
        'amount' => 1000,
        'method' => PaymentMethod::cases()[0]->value,
    ], $this->admin);

    expect($payment->exists)->toBeTrue();
    expect($invoice->fresh()->amount_paid)->toEqual('1000.00');

    $this->actingAs($this->admin)->get(route('finance.invoices.index'))->assertOk();
    $this->actingAs($this->admin)->get(route('finance.invoices.show', $invoice))->assertOk();
    $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
});
