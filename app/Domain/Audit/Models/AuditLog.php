<?php

namespace App\Domain\Audit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Deliberately does NOT use BelongsToTenant: audit rows must survive and
 * remain queryable even for platform-level actions with no tenant (a
 * super-admin suspending a structure), and the global scope's "no tenant in
 * context -> unfiltered" behaviour would be the wrong default for a log
 * that's meant to be read, not filtered away by accident.
 */
class AuditLog extends Model
{
    protected $fillable = [
        'structure_id',
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
