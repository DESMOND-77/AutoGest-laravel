<?php

use App\Domain\Finance\Enums\InvoiceStatus;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Services\OrderService;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Support\TenantContext;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    TenantContext::set($this->structure);
});

afterEach(fn () => TenantContext::clear());

it('creates an invoice for a walk-in buyer, unpaid by default', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'price' => 5000, 'stock_quantity' => 10]);

    $result = app(OrderService::class)->place(
        [['product_id' => $product->id, 'quantity' => 2]],
        null,
        'Jean Client',
    );
    $order = $result['order'];

    expect($order->invoice_id)->not->toBeNull();
    $invoice = Invoice::query()->findOrFail($order->invoice_id);
    expect($invoice->student_id)->toBeNull();
    expect((float) $invoice->amount_due)->toBe(10000.0);
    expect($invoice->status)->toBe(InvoiceStatus::Unpaid);
});

it('creates an invoice for a student buyer, linked to that student', function () {
    $student = Student::factory()->create(['structure_id' => $this->structure->id]);
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'price' => 3000, 'stock_quantity' => 10]);

    $result = app(OrderService::class)->place(
        [['product_id' => $product->id, 'quantity' => 1]],
        $student,
        null,
    );
    $order = $result['order'];

    $invoice = Invoice::query()->findOrFail($order->invoice_id);
    expect($invoice->student_id)->toBe($student->id);
});

it('allows a sale with insufficient stock and flags it, instead of blocking it', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'price' => 1000, 'stock_quantity' => 1]);

    $result = app(OrderService::class)->place(
        [['product_id' => $product->id, 'quantity' => 5]],
        null,
        'Jean Client',
    );

    expect($result['order']->exists)->toBeTrue();
    expect($result['lowStock'])->toBe([$product->name]);
    expect($product->fresh()->stock_quantity)->toBe(0);
});
