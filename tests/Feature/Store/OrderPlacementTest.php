<?php

use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\LedgerEntry;
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

it('decrements stock and creates an invoice when the order is for a student', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'price' => 8000, 'stock_quantity' => 5]);
    $student = Student::factory()->create(['structure_id' => $this->structure->id]);

    $result = app(OrderService::class)->place(
        [['product_id' => $product->id, 'quantity' => 2]],
        $student,
        null,
    );
    $order = $result['order'];

    expect($product->fresh()->stock_quantity)->toBe(3);
    expect((float) $order->total)->toBe(16000.0);

    $invoice = Invoice::query()->where('student_id', $student->id)->sole();
    expect((float) $invoice->amount_due)->toBe(16000.0);
    expect($order->fresh()->invoice_id)->toBe($invoice->id);
});

it('creates an invoice for a walk-in sale instead of journaling directly to the ledger', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'price' => 5000, 'stock_quantity' => 10]);

    $result = app(OrderService::class)->place(
        [['product_id' => $product->id, 'quantity' => 1]],
        null,
        'Client comptoir',
    );

    expect($result['order']->invoice_id)->not->toBeNull();
    expect(LedgerEntry::query()->count())->toBe(0);
});

it('allows an order that exceeds available stock, flooring stock at zero and flagging it', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'stock_quantity' => 1]);

    $result = app(OrderService::class)->place(
        [['product_id' => $product->id, 'quantity' => 5]],
        null,
        'Client',
    );

    expect($result['lowStock'])->toBe([$product->name]);
    expect($product->fresh()->stock_quantity)->toBe(0);
});
