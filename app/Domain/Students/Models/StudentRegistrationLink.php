<?php

namespace App\Domain\Students\Models;

use App\Domain\Students\Database\Factories\StudentRegistrationLinkFactory;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A public, tenant-scoped self-registration link for prospective students.
 * The row is looked up by token_hash *before* any tenant is known (see
 * StudentRegistrationLinkService::validate) - at that point
 * TenantContext::hasTenant() is false, so BelongsToTenant's global scope
 * contributes no WHERE clause and the lookup naturally searches across every
 * tenant, exactly once, with no special-casing needed. Every other query
 * against this model (the admin settings page) runs with a tenant already
 * resolved from the authenticated session, so it's transparently isolated
 * like any other tenant-scoped model.
 */
class StudentRegistrationLink extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return StudentRegistrationLinkFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'created_by',
        'label',
        'token_hash',
        'usage_count',
        'max_uses',
        'expires_at',
        'revoked_at',
        'last_used_at',
    ];

    protected $casts = [
        'usage_count' => 'integer',
        'max_uses' => 'integer',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeRevoked(Builder $query): Builder
    {
        return $query->whereNotNull('revoked_at');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
    }

    /**
     * Not revoked and not expired - independent of remaining uses, unlike
     * isUsable(). Used by the admin settings page ("is there a link to show
     * at all"), where a maxed-out but otherwise live link is still the
     * tenant's "current" link worth displaying.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function hasRemainingUses(): bool
    {
        return $this->max_uses === null || $this->usage_count < $this->max_uses;
    }

    /**
     * The full check a token must pass to actually be used for a public
     * registration - everything scopeActive() checks, plus remaining uses.
     * Deliberately does *not* check the tenant's own status (Active vs
     * Suspended/Pending/Deactivated): that's a separate, explicit check in
     * the service so a suspended tenant produces its own clear failure
     * reason instead of being folded into "invalid link".
     */
    public function isUsable(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired() && $this->hasRemainingUses();
    }

    public function markUsed(): void
    {
        $this->forceFill([
            'usage_count' => $this->usage_count + 1,
            'last_used_at' => Carbon::now(),
        ])->save();
    }
}
