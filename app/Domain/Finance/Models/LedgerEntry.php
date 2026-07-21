<?php

namespace App\Domain\Finance\Models;

use App\Domain\Finance\Database\Factories\LedgerEntryFactory;
use App\Domain\Finance\Enums\LedgerEntryType;
use App\Models\User;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerEntry extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return LedgerEntryFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'payment_id',
        'created_by',
        'type',
        'amount',
        'memo',
        'occurred_on',
    ];

    protected $casts = [
        'type' => LedgerEntryType::class,
        'amount' => 'decimal:2',
        'occurred_on' => 'date',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
