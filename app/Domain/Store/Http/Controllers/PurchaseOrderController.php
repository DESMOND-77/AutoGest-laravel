<?php

namespace App\Domain\Store\Http\Controllers;

use App\Domain\Store\Http\Requests\ReceivePurchaseOrderRequest;
use App\Domain\Store\Http\Requests\StorePurchaseOrderRequest;
use App\Domain\Store\Models\PurchaseOrder;
use App\Domain\Store\Models\Supplier;
use App\Domain\Store\Services\PurchaseOrderService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderService $purchaseOrders,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', PurchaseOrder::class);

        return view('store.purchase-orders.index', [
            'purchaseOrders' => PurchaseOrder::query()->with(['supplier', 'items.product'])->latest('ordered_at')->paginate(20),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $supplier = Supplier::query()->findOrFail($request->validated('supplier_id'));

        $order = $this->purchaseOrders->place($supplier, $request->validated('items'));

        return redirect()->route('store.purchase-orders.index')->with('status', "Commande fournisseur #{$order->id} créée.");
    }

    public function receive(ReceivePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->purchaseOrders->receive($purchaseOrder, array_filter($request->validated('received')));

        return redirect()->route('store.purchase-orders.index')->with('status', 'Réception enregistrée, stock mis à jour.');
    }
}
