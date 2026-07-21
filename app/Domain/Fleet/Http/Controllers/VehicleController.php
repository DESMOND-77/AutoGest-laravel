<?php

namespace App\Domain\Fleet\Http\Controllers;

use App\Domain\Fleet\Http\Requests\StoreFuelLogRequest;
use App\Domain\Fleet\Http\Requests\StoreMaintenanceLogRequest;
use App\Domain\Fleet\Http\Requests\StoreVehicleRequest;
use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Fleet\Services\AlertService;
use App\Domain\Fleet\Services\FleetService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function __construct(
        private readonly FleetService $fleet,
        private readonly AlertService $alerts,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Vehicle::class);

        return view('fleet.index', [
            'vehicles' => Vehicle::query()->orderBy('plate')->get(),
            'expiringSoon' => $this->alerts->expiringSoon()->pluck('id'),
        ]);
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        Vehicle::query()->create($request->validated());

        return redirect()->route('fleet.index')->with('status', 'Véhicule ajouté.');
    }

    public function show(Vehicle $vehicle): View
    {
        $this->authorize('viewAny', Vehicle::class);

        return view('fleet.show', [
            'vehicle' => $vehicle->load(['maintenanceLogs' => fn ($q) => $q->latest('performed_on'), 'fuelLogs' => fn ($q) => $q->latest('filled_on')]),
        ]);
    }

    public function storeMaintenance(StoreMaintenanceLogRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->fleet->logMaintenance($vehicle, $request->validated());

        return back()->with('status', 'Entretien enregistré.');
    }

    public function storeFuel(StoreFuelLogRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->fleet->logFuel($vehicle, $request->validated());

        return back()->with('status', 'Plein enregistré.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('delete', $vehicle);

        $vehicle->delete();

        return redirect()->route('fleet.index')->with('status', 'Véhicule supprimé.');
    }
}
