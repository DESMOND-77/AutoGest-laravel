<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Single post-login landing route ('dashboard'), fanning out to each
     * role's own area. Keeps Breeze's intended()-URL flow working without
     * every auth code path needing to know about roles.
     */
    public function __invoke(): RedirectResponse
    {
        $user = Auth::user();

        return match (true) {
            $user->hasRole('superadmin') => redirect()->route('superadmin.structures.index'),
            $user->hasRole('admin') => redirect()->route('admin.dashboard'),
            $user->hasRole('moniteur') => redirect()->route('moniteur.dashboard'),
            $user->hasRole('eleve') => redirect()->route('eleve.dashboard'),
            default => redirect()->route('profile.edit'),
        };
    }
}
