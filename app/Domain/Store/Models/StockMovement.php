<?php

namespace App\Domain\Store\Models;

use App\Domain\Store\Database\Factories\StockMovementFactory;
use App\Domain\Store\Enums\StockMovementType;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return StockMovementFactory::new();
    }

    protected $fillable = ['structure_id', 'product_id', 'type', 'quantity', 'reference', 'occurred_at'];

    protected $casts = [
        'type' => StockMovementType::class,
        'occurred_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
