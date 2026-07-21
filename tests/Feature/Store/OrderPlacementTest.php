<?php

use App\Domain\Finance\Enums\LedgerEntryType;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\LedgerEntry;
use App\Domain\Store\Exceptions\InsufficientStock;
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

    $order = app(OrderService::class)->place(
        [['product_id' => $product->id, 'quantity' => 2]],
        $student,
        null,
    );

    expect($product->fresh()->stock_quantity)->toBe(3);
    expect((float) $order->total)->toBe(16000.0);

    $invoice = Invoice::query()->where('student_id', $student->id)->sole();
    expect((float) $invoice->amount_due)->toBe(16000.0);
    expect($order->fresh()->invoice_id)->toBe($invoice->id);
});

it('journals a walk-in sale to the ledger instead of creating an invoice', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'price' => 5000, 'stock_quantity' => 10]);

    $order = app(OrderService::class)->place(
        [['product_id' => $product->id, 'quantity' => 1]],
        null,
        'Client comptoir',
    );

    expect($order->invoice_id)->toBeNull();

    $entry = LedgerEntry::query()->sole();
    expect((float) $entry->amount)->toBe(5000.0);
    expect($entry->type)->toBe(LedgerEntryType::Income);
});

it('rejects an order that exceeds available stock', function () {
    $product = Product::factory()->create(['structure_id' => $this->structure->id, 'stock_quantity' => 1]);

    expect(fn () => app(OrderService::class)->place(
        [['product_id' => $product->id, 'quantity' => 5]],
        null,
        'Client',
    ))->toThrow(InsufficientStock::class);

    expect($product->fresh()->stock_quantity)->toBe(1);
});
