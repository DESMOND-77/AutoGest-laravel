<?php

namespace App\Domain\Store\Services;

use App\Domain\Finance\Services\InvoicingService;
use App\Domain\Store\Enums\OrderStatus;
use App\Domain\Store\Models\Order;
use App\Domain\Store\Models\Product;
use App\Domain\Students\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * Store is allowed to depend on Finance (see the domain diagram) - unlike
 * Fleet, which deliberately is not. Every order, walk-in or student-linked,
 * gets a real Invoice - reusing PaymentService's existing, already-tested
 * partial-payment/cancellation/ledger pipeline rather than building a
 * parallel one. Stock going below zero WARNS (via the returned $lowStock
 * flag the controller surfaces to the user) rather than blocking the sale -
 * a walk-in customer at the counter cannot be told "come back later."
 */
class OrderService
{
    public function __construct(
        private readonly InvoicingService $invoicing,
    ) {}

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     * @return array{order: Order, lowStock: array<int, string>} product names that went under/at zero
     */
    public function place(array $items, ?Student $student, ?string $customerName): array
    {
        return DB::transaction(function () use ($items, $student, $customerName) {
            $lines = [];
            $total = 0;
            $lowStock = [];

            /** @var array<int, int> $quantitiesByProduct total quantity requested per product_id, across all lines */
            $quantitiesByProduct = [];
            foreach ($items as $item) {
                $quantitiesByProduct[$item['product_id']] = ($quantitiesByProduct[$item['product_id']] ?? 0) + $item['quantity'];
            }

            /** @var array<int, Product> $products */
            $products = [];
            foreach ($quantitiesByProduct as $productId => $totalQuantity) {
                $product = Product::query()->lockForUpdate()->findOrFail($productId);
                $products[$productId] = $product;

                if ($product->stock_quantity < $totalQuantity) {
                    $lowStock[] = $product->name;
                }
            }

            foreach ($items as $item) {
                $product = $products[$item['product_id']];
                $lines[] = ['product' => $product, 'quantity' => $item['quantity'], 'unit_price' => $product->price];
                $total += $product->price * $item['quantity'];
            }

            $order = Order::query()->create([
                'student_id' => $student?->id,
                'customer_name' => $customerName,
                'status' => OrderStatus::Confirmed,
                'total' => $total,
                'ordered_at' => now()->toDateString(),
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                ]);
            }

            foreach ($products as $productId => $product) {
                $newQuantity = max(0, $product->stock_quantity - $quantitiesByProduct[$productId]);
                $product->update(['stock_quantity' => $newQuantity]);
            }

            $buyerLabel = $student?->fullName() ?? $customerName ?? 'Client comptoir';
            $invoice = $this->invoicing->createGeneric($student, [
                'label' => "Vente boutique #{$order->id} - {$buyerLabel}",
                'amount_due' => $total,
                'issued_at' => now()->toDateString(),
            ]);
            $order->update(['invoice_id' => $invoice->id]);

            return ['order' => $order->fresh(), 'lowStock' => $lowStock];
        });
    }
}
