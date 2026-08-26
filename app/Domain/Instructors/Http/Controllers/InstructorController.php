<?php

namespace App\Domain\Instructors\Http\Controllers;

use App\Domain\Instructors\Http\Requests\StoreInstructorRequest;
use App\Domain\Instructors\Http\Requests\UpdateInstructorRequest;
use App\Domain\Instructors\Models\Instructor;
use App\Domain\Instructors\Repositories\InstructorRepositoryInterface;
use App\Domain\Users\Services\UserManagementService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InstructorController extends Controller
{
    public function __construct(
        private readonly InstructorRepositoryInterface $instructors,
        private readonly UserManagementService $users,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Instructor::class);

        return view('instructors.index', [
            'instructors' => $this->instructors->paginate(),
        ]);
    }

    public function show(Instructor $instructor): View
    {
        $this->authorize('view', $instructor);

        return view('instructors.show', [
            'instructor' => $instructor->load(['user', 'availabilities']),
        ]);
    }

    public function store(StoreInstructorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = $this->users->createAccount([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => 'moniteur',
        ], Auth::user());

        try {
            $this->instructors->create([
                'user_id' => $user->id,
                'license_number' => $data['license_number'] ?? null,
                'specialties' => $data['specialties'] ?? null,
                'hire_date' => $data['hire_date'] ?? null,
            ]);
        } catch (\Throwable $e) {
            // UserManagementService::createAccount() already committed the User row
            // (and sent the reset-password email) in its own transaction, so a failure
            // here would otherwise leave an orphaned account with no Instructor profile.
            $user->delete();

            return back()->withErrors(['instructor' => 'La création du moniteur a échoué, veuillez réessayer.']);
        }

        return redirect()->route('instructors.index')->with('status', 'Moniteur ajouté.');
    }

    public function update(UpdateInstructorRequest $request, Instructor $instructor): RedirectResponse
    {
        $instructor->update($request->validated());

        return redirect()->route('instructors.show', $instructor)->with('status', 'Moniteur mis à jour.');
    }

    public function destroy(Instructor $instructor): RedirectResponse
    {
        $this->authorize('delete', $instructor);

        $this->users->deactivate(User::query()->findOrFail($instructor->user_id), Auth::user());

        $instructor->delete();

        return redirect()->route('instructors.index')->with('status', 'Moniteur supprimé.');
    }
}
