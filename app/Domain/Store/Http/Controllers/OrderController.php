<?php

namespace App\Domain\Store\Http\Controllers;

use App\Domain\Store\Exceptions\InsufficientStock;
use App\Domain\Store\Http\Requests\StoreOrderRequest;
use App\Domain\Store\Models\Order;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Services\OrderService;
use App\Domain\Students\Models\Student;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Order::class);

        return view('store.orders.index', [
            'orders' => Order::query()->with(['student', 'items.product'])->latest('ordered_at')->paginate(20),
            'products' => Product::query()->where('active', true)->where('stock_quantity', '>', 0)->get(),
            'students' => Student::query()->orderBy('last_name')->get(),
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $student = $request->validated('student_id')
            ? Student::query()->find($request->validated('student_id'))
            : null;

        try {
            $order = $this->orders->place(
                $request->validated('items'),
                $student,
                $request->validated('customer_name'),
            );
        } catch (InsufficientStock $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('store.orders.index')->with('status', "Commande #{$order->id} enregistrée.");
    }
}
