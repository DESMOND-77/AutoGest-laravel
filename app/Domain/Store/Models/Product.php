<?php

namespace App\Domain\Store\Models;

use App\Domain\Store\Database\Factories\ProductFactory;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return ProductFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'supplier_id',
        'name',
        'sku',
        'barcode',
        'category',
        'price',
        'cost_price',
        'stock_quantity',
        'reorder_threshold',
        'active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
