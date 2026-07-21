<?php

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

/**
 * Deliberately takes only primitives + a generic Model, never a domain
 * type — Audit depends on nothing but Core, so any other domain can log to
 * it without Audit ever needing to know Students/Finance/Fleet exist.
 */
class AuditService
{
    public function log(string $action, ?Model $auditable = null, array $old = [], array $new = [], ?User $actor = null): AuditLog
    {
        return AuditLog::query()->create([
            'structure_id' => $auditable?->getAttribute('structure_id') ?? $actor?->structure_id,
            'user_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
            'ip_address' => Request::ip(),
        ]);
    }
}
