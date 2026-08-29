<?php

namespace App\Domain\Store\Http\Controllers;

use App\Domain\Store\Models\Order;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Models\PurchaseOrder;
use App\Domain\Store\Models\Supplier;
use App\Domain\Store\Services\StoreReportService;
use App\Domain\Students\Models\Student;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function __construct(
        private readonly StoreReportService $reports,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Order::class);

        return view('store.index', [
            'orders' => Order::query()->with(['student', 'items.product'])->latest('ordered_at')->paginate(20, ['*'], 'ordersPage'),
            // No stock filter: selling at or below zero stock warns (see
            // OrderService), it does not block - so a zero-stock product must
            // still be selectable at the counter.
            'products' => Product::query()->where('active', true)->get(),
            'students' => Student::query()->orderBy('last_name')->get(),
            'allProducts' => Product::query()->orderBy('name')->get(),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'purchaseOrders' => PurchaseOrder::query()->with(['supplier', 'items.product'])->latest('ordered_at')->paginate(20, ['*'], 'purchaseOrdersPage'),
            ...$this->reports->dashboard(),
        ]);
    }
}
