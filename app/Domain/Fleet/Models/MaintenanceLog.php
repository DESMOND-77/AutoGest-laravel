<?php

namespace App\Domain\Fleet\Models;

use App\Domain\Fleet\Database\Factories\MaintenanceLogFactory;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLog extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return MaintenanceLogFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'vehicle_id',
        'type',
        'description',
        'cost',
        'mileage',
        'performed_on',
        'next_due_on',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'performed_on' => 'date',
        'next_due_on' => 'date',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
