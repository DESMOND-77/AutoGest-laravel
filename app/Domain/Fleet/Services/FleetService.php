<?php

namespace App\Domain\Fleet\Services;

use App\Domain\Fleet\Events\VehicleExpenseRecorded;
use App\Domain\Fleet\Models\FuelLog;
use App\Domain\Fleet\Models\MaintenanceLog;
use App\Domain\Fleet\Models\Vehicle;
use Illuminate\Support\Facades\Event;

class FleetService
{
    public function logMaintenance(Vehicle $vehicle, array $data): MaintenanceLog
    {
        $log = MaintenanceLog::query()->create($data + ['vehicle_id' => $vehicle->id]);

        $this->syncMileage($vehicle, $data['mileage'] ?? null);

        if ((float) ($data['cost'] ?? 0) > 0) {
            Event::dispatch(new VehicleExpenseRecorded(
                $vehicle,
                (float) $data['cost'],
                "Entretien {$data['type']} — {$vehicle->plate}",
                $data['performed_on'],
            ));
        }

        return $log;
    }

    public function logFuel(Vehicle $vehicle, array $data): FuelLog
    {
        $log = FuelLog::query()->create($data + ['vehicle_id' => $vehicle->id]);

        $this->syncMileage($vehicle, $data['mileage'] ?? null);

        Event::dispatch(new VehicleExpenseRecorded(
            $vehicle,
            (float) $data['cost'],
            "Carburant — {$vehicle->plate}",
            $data['filled_on'],
        ));

        return $log;
    }

    private function syncMileage(Vehicle $vehicle, ?int $mileage): void
    {
        if ($mileage !== null && $mileage > $vehicle->mileage) {
            $vehicle->update(['mileage' => $mileage]);
        }
    }
}
