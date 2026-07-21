<?php

namespace App\Domain\Store\Models;

use App\Domain\Finance\Models\Invoice;
use App\Domain\Store\Database\Factories\OrderFactory;
use App\Domain\Store\Enums\OrderStatus;
use App\Domain\Students\Models\Student;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return OrderFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'student_id',
        'invoice_id',
        'customer_name',
        'status',
        'total',
        'ordered_at',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'total' => 'decimal:2',
        'ordered_at' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
