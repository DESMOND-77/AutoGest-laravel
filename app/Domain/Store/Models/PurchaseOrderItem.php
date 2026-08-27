<?php

namespace App\Domain\Store\Models;

use App\Domain\Store\Database\Factories\PurchaseOrderItemFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * No structure_id column and no BelongsToTenant here - a purchase order item is
 * only ever reached through its parent PurchaseOrder, which is itself tenant-scoped.
 */
class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return PurchaseOrderItemFactory::new();
    }

    protected $fillable = ['purchase_order_id', 'product_id', 'quantity', 'quantity_received'];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
