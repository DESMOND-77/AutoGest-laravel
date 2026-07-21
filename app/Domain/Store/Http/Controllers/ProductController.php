<?php

namespace App\Domain\Store\Http\Controllers;

use App\Domain\Store\Http\Requests\StoreProductRequest;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Models\Supplier;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Product::class);

        return view('store.products.index', [
            'products' => Product::query()->orderBy('name')->get(),
            'suppliers' => Supplier::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        Product::query()->create($request->validated() + ['active' => true]);

        return back()->with('status', 'Produit ajouté.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return back()->with('status', 'Produit supprimé.');
    }
}
