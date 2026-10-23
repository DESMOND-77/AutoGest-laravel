<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Support\Ui\Preferences;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Synchronise les préférences d'apparence entre appareils (additif, §1.3).
     * Aucune valeur n'est stockée sans passer par la liste blanche Preferences.
     */
    public function updateAppearance(Request $request): JsonResponse
    {
        $preferences = Preferences::fromArray([
            'theme' => $request->input('theme'),
            'accent' => $request->input('accent'),
            'density' => $request->input('density'),
            'perf' => $request->input('perf'),
        ]);

        $request->user()->forceFill(['ui_preferences' => $preferences->toDatabase()])->save();

        return response()->json(['saved' => true, 'preferences' => $preferences->toArray()]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
