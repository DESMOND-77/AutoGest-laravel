<?php

namespace App\Domain\Store\Services;

use App\Domain\Finance\Enums\LedgerEntryType;
use App\Domain\Finance\Services\InvoicingService;
use App\Domain\Finance\Services\LedgerService;
use App\Domain\Store\Enums\OrderStatus;
use App\Domain\Store\Exceptions\InsufficientStock;
use App\Domain\Store\Models\Order;
use App\Domain\Store\Models\Product;
use App\Domain\Students\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * Store is allowed to depend on Finance (see the domain diagram) - unlike
 * Fleet, which deliberately is not. An order for a student becomes an
 * Invoice the student can pay off like any other; a walk-in sale (no
 * student, e.g. the legacy code_rousseau.php "freeform buyer" case) is
 * journaled straight to the ledger as cash income.
 */
class OrderService
{
    public function __construct(
        private readonly InvoicingService $invoicing,
        private readonly LedgerService $ledger,
    ) {}

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    public function place(array $items, ?Student $student, ?string $customerName): Order
    {
        return DB::transaction(function () use ($items, $student, $customerName) {
            $lines = [];
            $total = 0;

            foreach ($items as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item['product_id']);

                if ($product->stock_quantity < $item['quantity']) {
                    throw InsufficientStock::forProduct($product->name);
                }

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

                $line['product']->decrement('stock_quantity', $line['quantity']);
            }

            if ($student) {
                $invoice = $this->invoicing->createForStudent($student, [
                    'label' => 'Commande boutique #'.$order->id,
                    'amount_due' => $total,
                    'issued_at' => now()->toDateString(),
                ]);
                $order->update(['invoice_id' => $invoice->id]);
            } else {
                $this->ledger->recordManual([
                    'type' => LedgerEntryType::Income->value,
                    'amount' => $total,
                    'memo' => "Vente comptoir #{$order->id}".($customerName ? " - {$customerName}" : ''),
                    'occurred_on' => now()->toDateString(),
                ]);
            }

            return $order;
        });
    }
}
