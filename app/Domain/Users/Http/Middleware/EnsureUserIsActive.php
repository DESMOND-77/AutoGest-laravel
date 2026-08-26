<?php

namespace App\Domain\Users\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Appended globally to the `web` group (like ResolveTenant) so a session
 * that was valid when it started still gets cut off the moment an admin
 * deactivates the account mid-session — LoginRequest's is_active check
 * alone only stops a *future* login attempt, not an already-open one.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        return $next($request);
    }
}
