<?php

namespace App\Domain\Fleet\Models;

use App\Domain\Fleet\Database\Factories\FuelLogFactory;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelLog extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return FuelLogFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'vehicle_id',
        'liters',
        'cost',
        'mileage',
        'filled_on',
    ];

    protected $casts = [
        'liters' => 'decimal:2',
        'cost' => 'decimal:2',
        'filled_on' => 'date',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
