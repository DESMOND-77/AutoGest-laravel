<?php

namespace App\Domain\Store\Models;

use App\Domain\Store\Database\Factories\PurchaseOrderFactory;
use App\Domain\Store\Enums\PurchaseOrderStatus;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return PurchaseOrderFactory::new();
    }

    protected $fillable = ['structure_id', 'supplier_id', 'status', 'ordered_at'];

    protected $casts = [
        'status' => PurchaseOrderStatus::class,
        'ordered_at' => 'date',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
