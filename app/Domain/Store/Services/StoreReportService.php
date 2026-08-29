<?php

namespace App\Domain\Store\Services;

use App\Domain\Finance\Enums\InvoiceStatus;
use App\Domain\Store\Enums\OrderStatus;
use App\Domain\Store\Models\Order;
use App\Domain\Store\Models\OrderItem;
use App\Domain\Store\Models\Product;
use App\Support\TenantContext;
use Illuminate\Support\Collection;

class StoreReportService
{
    /**
     * @return array{revenueToday: float, revenueThisWeek: float, revenueThisMonth: float, revenueThisYear: float, salesCount: int, topProducts: Collection, criticalStock: Collection, pendingBalance: float}
     */
    public function dashboard(): array
    {
        return [
            'revenueToday' => $this->revenueSince(now()->startOfDay()),
            'revenueThisWeek' => $this->revenueSince(now()->startOfWeek()),
            'revenueThisMonth' => $this->revenueSince(now()->startOfMonth()),
            'revenueThisYear' => $this->revenueSince(now()->startOfYear()),
            'salesCount' => Order::query()->where('status', '!=', OrderStatus::Cancelled->value)->count(),
            'topProducts' => $this->topProducts(),
            'criticalStock' => Product::query()
                ->whereNotNull('reorder_threshold')
                ->whereColumn('stock_quantity', '<', 'reorder_threshold')
                ->orderBy('name')
                ->get(),
            'pendingBalance' => $this->pendingBalance(),
        ];
    }

    private function revenueSince(\DateTimeInterface $since): float
    {
        return (float) Order::query()
            ->where('ordered_at', '>=', $since->format('Y-m-d'))
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->sum('total');
    }

    private function topProducts(int $limit = 5): Collection
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.structure_id', TenantContext::id())
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->selectRaw('products.name as name, SUM(order_items.quantity) as quantity, SUM(order_items.quantity * order_items.unit_price) as revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['name' => $row->name, 'quantity' => (int) $row->quantity, 'revenue' => (float) $row->revenue]);
    }

    private function pendingBalance(): float
    {
        return (float) Order::query()
            ->whereNotNull('invoice_id')
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->with('invoice')
            ->get()
            ->filter(fn (Order $order) => $order->invoice && in_array($order->invoice->status, [InvoiceStatus::Unpaid, InvoiceStatus::Partial], true))
            ->sum(fn (Order $order) => $order->invoice->balanceDue());
    }
}
