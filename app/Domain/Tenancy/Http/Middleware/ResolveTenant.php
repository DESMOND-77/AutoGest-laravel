<?php

namespace App\Domain\Tenancy\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the tenant for the authenticated user and binds it to
 * TenantContext for the duration of the request. Super-admin users
 * (structure_id = null) simply leave no tenant bound - routes under their
 * area never touch tenant-scoped models.
 */
class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->structure) {
            TenantContext::set($user->structure);
        }

        try {
            return $next($request);
        } finally {
            TenantContext::clear();
        }
    }
}
