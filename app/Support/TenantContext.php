<?php

namespace App\Support;

use App\Domain\Tenancy\Models\Structure;

/**
 * Request-lifetime holder for the current tenant, set by ResolveTenant
 * middleware. Unlike current_structure_id() in the legacy app, there is no
 * silent fallback to a default tenant id - code that needs a tenant and finds
 * none must fail loudly.
 */
class TenantContext
{
    private static ?Structure $structure = null;

    public static function set(Structure $structure): void
    {
        self::$structure = $structure;
    }

    public static function clear(): void
    {
        self::$structure = null;
    }

    public static function hasTenant(): bool
    {
        return self::$structure !== null;
    }

    public static function current(): Structure
    {
        return self::$structure ?? throw new \RuntimeException(
            'No tenant resolved in the current request context.'
        );
    }

    public static function id(): int
    {
        return self::current()->id;
    }
}
