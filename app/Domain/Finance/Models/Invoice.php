<?php

namespace App\Domain\Finance\Models;

use App\Domain\Finance\Database\Factories\InvoiceFactory;
use App\Domain\Finance\Enums\InvoiceStatus;
use App\Domain\Students\Models\Student;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return InvoiceFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'student_id',
        'training_package_id',
        'label',
        'amount_due',
        'amount_paid',
        'status',
        'issued_at',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'status' => InvoiceStatus::class,
        'issued_at' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function trainingPackage(): BelongsTo
    {
        return $this->belongsTo(TrainingPackage::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function balanceDue(): float
    {
        return (float) $this->amount_due - (float) $this->amount_paid;
    }
}
