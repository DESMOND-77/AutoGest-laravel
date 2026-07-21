<?php

namespace App\Domain\Tenancy\Http\Controllers;

use App\Domain\Tenancy\Http\Requests\RegisterStructureRequest;
use App\Domain\Tenancy\Services\StructureOnboardingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StructureRegistrationController extends Controller
{
    public function __construct(
        private readonly StructureOnboardingService $onboarding,
    ) {}

    public function create(): View
    {
        return view('tenancy.register');
    }

    public function store(RegisterStructureRequest $request): RedirectResponse
    {
        $this->onboarding->register($request->validated());

        return redirect()->route('login')->with(
            'status',
            "Votre auto-école a bien été enregistrée. Elle est en attente de validation par l'administrateur de la plateforme avant que vous puissiez vous connecter."
        );
    }
}
