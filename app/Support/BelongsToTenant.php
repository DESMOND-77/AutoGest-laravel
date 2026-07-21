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
 * Note: implicit route-model binding (SubstituteBindings) runs before
 * ResolveTenant in the middleware stack, so a {student}/{invoice}/... bound
 * straight from the URL is resolved *before* this scope has a tenant to
 * filter on. Cross-tenant access is still blocked — every controller that
 * accepts a bound model explicitly checks the owning Policy — but the
 * response is a 403 from the Policy rather than a 404 from the scope. The
 * scope's own protection is fully active for every query built inside a
 * controller/service (index listings, repositories, etc.), which is most of
 * what it's for.
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
