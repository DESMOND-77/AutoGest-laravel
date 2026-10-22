<?php

namespace App\Domain\Students\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applied only to the eleve routes that require a verified account
 * (dashboard, planning, quiz, dossier) - not to the OTP screen's own routes,
 * or every request would redirect back to itself.
 */
class EnsureEmailOtpVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('eleve') && $user->email_verified_at === null) {
            return redirect()->route('eleve.otp.show');
        }

        return $next($request);
    }
}
