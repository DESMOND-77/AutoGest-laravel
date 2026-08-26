<?php

namespace App\Domain\Instructors\Http\Controllers;

use App\Domain\Instructors\Http\Requests\StoreInstructorRequest;
use App\Domain\Instructors\Http\Requests\UpdateInstructorRequest;
use App\Domain\Instructors\Models\Instructor;
use App\Domain\Instructors\Repositories\InstructorRepositoryInterface;
use App\Domain\Users\Services\UserManagementService;
use App\Http\Controllers\Controller;
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

        $this->instructors->create([
            'user_id' => $user->id,
            'license_number' => $data['license_number'] ?? null,
            'specialties' => $data['specialties'] ?? null,
            'hire_date' => $data['hire_date'] ?? null,
        ]);

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

        $instructor->delete();

        return redirect()->route('instructors.index')->with('status', 'Moniteur supprimé.');
    }
}
