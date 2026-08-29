<?php

namespace App\Domain\Students\Http\Controllers;

use App\Domain\Students\Enums\CourseType;
use App\Domain\Students\Enums\LicenseCategory;
use App\Domain\Students\Exceptions\DuplicateRegistration;
use App\Domain\Students\Exceptions\InvalidRegistrationLink;
use App\Domain\Students\Http\Requests\PublicStudentRegistrationRequest;
use App\Domain\Students\Models\StudentRegistrationLink;
use App\Domain\Students\Services\PublicStudentRegistrationService;
use App\Domain\Students\Services\StudentRegistrationLinkService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The only public-facing side of this feature. Nothing here ever reads a
 * tenant/structure/school id from the request - the token is the sole
 * source of truth for which tenant a visitor is registering with (§68 of
 * the spec). A visitor cannot even name a different tenant: no field for it
 * exists on PublicStudentRegistrationRequest.
 */
class PublicStudentRegistrationController extends Controller
{
    public function __construct(
        private readonly StudentRegistrationLinkService $links,
        private readonly PublicStudentRegistrationService $registration,
    ) {}

    public function show(Request $request): View
    {
        $token = (string) $request->query('token', '');

        if ($token === '') {
            return view('register.student', ['state' => 'invalid']);
        }

        try {
            $link = $this->links->validate($token);
        } catch (InvalidRegistrationLink $e) {
            return view('register.student', ['state' => $e->reason]);
        }

        return view('register.student', [
            'state' => 'form',
            'token' => $token,
            'structure' => $link->structure,
            'licenseCategories' => LicenseCategory::cases(),
            'courseTypes' => CourseType::cases(),
        ]);
    }

    public function store(PublicStudentRegistrationRequest $request): View|RedirectResponse
    {
        $token = $request->validated('registration_token');

        try {
            $this->registration->register($token, $request->accountData(), $request->studentData());
        } catch (InvalidRegistrationLink $e) {
            return view('register.student', ['state' => $e->reason]);
        } catch (DuplicateRegistration $e) {
            // Never flash a password back into the session.
            $request->flashExcept(['password', 'password_confirmation']);
            $link = $this->safeLink($token);

            return view('register.student', [
                'state' => 'form',
                'token' => $token,
                'structure' => $link?->structure,
                'licenseCategories' => LicenseCategory::cases(),
                'courseTypes' => CourseType::cases(),
                'duplicateError' => $e->getMessage(),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route('eleve.otp.show');
    }

    /**
     * Re-resolves the link for re-rendering the form after a duplicate
     * error - a failed *duplicate* check still means the token itself was
     * fine a moment ago, so this is expected to succeed; if the link was
     * revoked in the split second between the two, falling back to null
     * just means the school name won't show, not a broken page.
     */
    private function safeLink(string $token): ?StudentRegistrationLink
    {
        try {
            return $this->links->validate($token);
        } catch (InvalidRegistrationLink) {
            return null;
        }
    }
}
