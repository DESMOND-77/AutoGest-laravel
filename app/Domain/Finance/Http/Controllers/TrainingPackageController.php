<?php

namespace App\Domain\Finance\Http\Controllers;

use App\Domain\Finance\Http\Requests\StoreTrainingPackageRequest;
use App\Domain\Finance\Models\TrainingPackage;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TrainingPackageController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', TrainingPackage::class);

        return view('finance.packages.index', [
            'packages' => TrainingPackage::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreTrainingPackageRequest $request): RedirectResponse
    {
        TrainingPackage::query()->create($request->validated() + ['active' => true]);

        return redirect()->route('finance.packages.index')->with('status', 'Forfait créé.');
    }

    public function update(StoreTrainingPackageRequest $request, TrainingPackage $package): RedirectResponse
    {
        $this->authorize('update', $package);

        $package->update($request->validated());

        return redirect()->route('finance.packages.index')->with('status', 'Forfait mis à jour.');
    }

    public function destroy(TrainingPackage $package): RedirectResponse
    {
        $this->authorize('delete', $package);

        $package->delete();

        return redirect()->route('finance.packages.index')->with('status', 'Forfait supprimé.');
    }
}
