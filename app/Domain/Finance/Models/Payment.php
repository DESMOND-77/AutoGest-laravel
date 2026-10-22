<?php

namespace App\Domain\Finance\Models;

use App\Domain\Finance\Database\Factories\PaymentFactory;
use App\Domain\Finance\Enums\PaymentMethod;
use App\Models\User;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return PaymentFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'invoice_id',
        'recorded_by',
        'amount',
        'method',
        'paid_at',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'method' => PaymentMethod::class,
        'paid_at' => 'date',
        'cancelled_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function ledgerEntry(): HasOne
    {
        return $this->hasOne(LedgerEntry::class);
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }
}
