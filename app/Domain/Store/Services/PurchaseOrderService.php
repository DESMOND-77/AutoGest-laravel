<?php

namespace App\Domain\Store\Services;

use App\Domain\Store\Enums\PurchaseOrderStatus;
use App\Domain\Store\Enums\StockMovementType;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Models\PurchaseOrder;
use App\Domain\Store\Models\Supplier;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    public function place(Supplier $supplier, array $items): PurchaseOrder
    {
        return DB::transaction(function () use ($supplier, $items) {
            $order = PurchaseOrder::query()->create([
                'supplier_id' => $supplier->id,
                'status' => PurchaseOrderStatus::Pending,
                'ordered_at' => now()->toDateString(),
            ]);

            foreach ($items as $item) {
                // Resolved through the tenant-scoped global scope, so a
                // cross-tenant product_id throws instead of silently creating
                // a dangling PurchaseOrderItem (plain `exists:products,id`
                // validation does not respect the tenant scope).
                $product = Product::query()->findOrFail($item['product_id']);

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                ]);
            }

            return $order;
        });
    }

    /**
     * @param  array<int, int>  $receivedQuantities  product_id => quantity received in this delivery
     */
    public function receive(PurchaseOrder $order, array $receivedQuantities): PurchaseOrder
    {
        return DB::transaction(function () use ($order, $receivedQuantities) {
            foreach ($order->items as $item) {
                // The view's `max=` attribute is client-side only; clamp here
                // so quantity_received can never exceed what was ordered,
                // whatever a raw POST claims.
                $outstanding = max(0, $item->quantity - $item->quantity_received);
                $received = min((int) ($receivedQuantities[$item->product_id] ?? 0), $outstanding);

                if ($received <= 0) {
                    continue;
                }

                $item->increment('quantity_received', $received);

                $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
                $product->increment('stock_quantity', $received);

                $product->stockMovements()->create([
                    'type' => StockMovementType::Reception,
                    'quantity' => $received,
                    'reference' => "Commande fournisseur #{$order->id}",
                    'occurred_at' => now(),
                ]);
            }

            $order->refresh();
            $fullyReceived = $order->items->every(fn ($item) => $item->quantity_received >= $item->quantity);
            $anyReceived = $order->items->contains(fn ($item) => $item->quantity_received > 0);

            $order->update([
                'status' => match (true) {
                    $fullyReceived => PurchaseOrderStatus::Received,
                    $anyReceived => PurchaseOrderStatus::PartiallyReceived,
                    default => $order->status,
                },
            ]);

            return $order;
        });
    }
}
