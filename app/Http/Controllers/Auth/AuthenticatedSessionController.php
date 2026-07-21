<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Tenancy\Enums\StructureStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        // The super-admin (structure_id = null) has no tenant to gate on.
        if ($user->structure_id !== null) {
            $status = $user->structure?->status;

            if ($status !== StructureStatus::Active) {
                Auth::logout();

                throw ValidationException::withMessages([
                    'email' => [$this->statusMessage($status)],
                ]);
            }
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function statusMessage(?StructureStatus $status): string
    {
        return match ($status) {
            StructureStatus::Pending => "Votre établissement est en attente de validation par l'administrateur de la plateforme.",
            StructureStatus::Suspended => 'Votre établissement est actuellement suspendu.',
            StructureStatus::Deactivated => 'Votre établissement a été désactivé.',
            default => 'Accès refusé.',
        };
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
