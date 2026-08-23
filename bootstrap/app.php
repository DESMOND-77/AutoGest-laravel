<?php

use App\Domain\Students\Http\Middleware\EnsureEmailOtpVerified;
use App\Domain\Tenancy\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', ResolveTenant::class);
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'otp.verified' => EnsureEmailOtpVerified::class,
        ]);

        // ResolveTenant must run before implicit route-model binding
        // (SubstituteBindings) so the tenant global scope is active when
        // {student}/{invoice}/... are resolved from the URL. Without this,
        // a cross-tenant resource still resolves (unscoped) and is only
        // rejected afterwards by the controller's policy check.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: ResolveTenant::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
