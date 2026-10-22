<?php

namespace App\Domain\Recyclage\Models;

use App\Domain\Recyclage\Database\Factories\RecyclageEntryFactory;
use App\Domain\Recyclage\Enums\RecyclageMotif;
use App\Models\User;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A one-off, billable session for someone who is NOT an enrolled Student -
 * deliberately has no relation to App\Domain\Students\Models\Student, even
 * when the same person happens to be a former student elsewhere.
 */
class RecyclageEntry extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return RecyclageEntryFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'full_name',
        'motif',
        'phone',
        'instructor_id',
        'session_date',
        'amount',
    ];

    protected $casts = [
        'motif' => RecyclageMotif::class,
        'session_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
