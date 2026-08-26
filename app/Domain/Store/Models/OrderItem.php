<?php

namespace App\Domain\Store\Models;

use App\Domain\Store\Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * No structure_id column and no BelongsToTenant here - an order item is
 * only ever reached through its parent Order, which is itself tenant-scoped.
 * Adding a redundant column here would just be another place for the two to
 * drift apart.
 */
class OrderItem extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return OrderItemFactory::new();
    }

    protected $fillable = ['order_id', 'product_id', 'quantity', 'unit_price'];

    protected $casts = ['unit_price' => 'decimal:2'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
