<?php

namespace App\Domain\Fleet\Models;

use App\Domain\Fleet\Database\Factories\VehicleFactory;
use App\Domain\Fleet\Enums\VehicleStatus;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return VehicleFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'plate',
        'brand',
        'model',
        'year',
        'category',
        'color',
        'mileage',
        'status',
        'technical_inspection_expires_at',
        'insurance_expires_at',
    ];

    protected $casts = [
        'status' => VehicleStatus::class,
        'technical_inspection_expires_at' => 'date',
        'insurance_expires_at' => 'date',
    ];

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class);
    }

    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class);
    }
}
