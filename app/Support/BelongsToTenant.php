<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Every scoped query and every create() is filtered/stamped with the current
 * tenant automatically. This is what makes the tenant-isolation bugs found in
 * the legacy app (admin/eleves.php edit+delete with no structure_id filter,
 * moniteur/evaluation.php reading a student with no ownership check)
 * structurally impossible instead of dependent on each query remembering to
 * add the filter by hand.
 *
 * Note: ResolveTenant is given explicit middleware priority (see
 * bootstrap/app.php) to run before SubstituteBindings, so this scope is
 * already active by the time a {student}/{invoice}/... route parameter is
 * resolved. A URL pointing at another tenant's resource simply doesn't match
 * any row and 404s before the controller/Policy ever runs. Every controller
 * still explicitly checks the owning Policy as defense in depth (e.g. same
 * tenant but wrong role), but cross-tenant access no longer depends on that
 * check alone.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (TenantContext::hasTenant()) {
                $builder->where(
                    $builder->getModel()->getTable().'.structure_id',
                    TenantContext::id()
                );
            }
        });

        static::creating(function (Model $model) {
            if (! $model->structure_id && TenantContext::hasTenant()) {
                $model->structure_id = TenantContext::id();
            }
        });
    }

    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }
}
