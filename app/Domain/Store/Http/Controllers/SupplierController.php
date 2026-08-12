<?php

namespace App\Domain\Store\Http\Controllers;

use App\Domain\Store\Models\Supplier;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Supplier::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
        ]);

        Supplier::query()->create($data);

        return back()->with('status', 'Fournisseur ajouté.');
    }
}
