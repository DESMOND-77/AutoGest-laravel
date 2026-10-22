<?php

namespace App\Domain\Students\Http\Controllers;

use App\Domain\Students\Http\Requests\GenerateRegistrationLinkRequest;
use App\Domain\Students\Models\StudentRegistrationLink;
use App\Domain\Students\Services\StudentRegistrationLinkService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Tenant-admin management of the public student-registration link. The
 * plain token is only ever available right after generate()/regenerate() -
 * see StudentRegistrationLinkService's docblock - so this controller flashes
 * it once for the immediately-following page render and never again.
 */
class StudentRegistrationLinkController extends Controller
{
    public function __construct(
        private readonly StudentRegistrationLinkService $links,
    ) {}

    public function show(): View
    {
        $this->authorize('viewAny', StudentRegistrationLink::class);

        $link = $this->links->getActiveLink(Auth::user()->structure);

        return view('settings.student-registration', [
            'link' => $link,
            'revealedToken' => session('registration_link_token'),
        ]);
    }

    public function generate(GenerateRegistrationLinkRequest $request): RedirectResponse
    {
        ['link' => $link, 'token' => $token] = $this->links->generate(
            Auth::user()->structure,
            Auth::user(),
            $request->validated('label'),
        );

        return redirect()->route('settings.student-registration.show')
            ->with('status', 'Lien d\'inscription généré.')
            ->with('registration_link_token', $token);
    }

    public function regenerate(GenerateRegistrationLinkRequest $request): RedirectResponse
    {
        $current = $this->links->getActiveLink(Auth::user()->structure);

        if ($current) {
            $this->authorize('regenerate', $current);
        } else {
            $this->authorize('create', StudentRegistrationLink::class);
        }

        ['token' => $token] = $this->links->regenerate(
            Auth::user()->structure,
            Auth::user(),
            $request->validated('label'),
        );

        return redirect()->route('settings.student-registration.show')
            ->with('status', 'Lien d\'inscription régénéré. L\'ancien lien ne fonctionne plus.')
            ->with('registration_link_token', $token);
    }

    public function revoke(): RedirectResponse
    {
        $current = $this->links->getActiveLink(Auth::user()->structure);

        if (! $current) {
            return redirect()->route('settings.student-registration.show');
        }

        $this->authorize('revoke', $current);

        $this->links->revoke($current);

        return redirect()->route('settings.student-registration.show')
            ->with('status', 'Lien d\'inscription révoqué.');
    }
}
