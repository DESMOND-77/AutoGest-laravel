<?php

namespace App\Domain\Users\Http\Controllers;

use App\Domain\Students\Models\Student;
use App\Domain\Users\Http\Requests\StoreUserRequest;
use App\Domain\Users\Services\UserManagementService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly UserManagementService $users,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $roleFilter = $request->query('role');
        $roleFilter = in_array($roleFilter, ['admin', 'moniteur', 'eleve'], true) ? $roleFilter : null;

        $query = User::query()->with('roles')->orderBy('name');

        if ($roleFilter) {
            $query->role($roleFilter);
        }

        $preselectedStudent = $request->filled('student')
            ? Student::query()->whereNull('user_id')->find($request->integer('student'))
            : null;

        return view('users.index', [
            'users' => $query->paginate(20)->withQueryString(),
            'roleFilter' => $roleFilter,
            'roleCounts' => collect(['admin', 'moniteur', 'eleve'])
                ->mapWithKeys(fn (string $role) => [$role => User::role($role)->count()]),
            'unlinkedStudents' => Student::query()->whereNull('user_id')->orderBy('last_name')->get(),
            'preselectedStudent' => $preselectedStudent,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->users->createAccount($request->validated(), Auth::user());

        return redirect()->route('settings.users.index')
            ->with('status', 'Compte créé. Un lien de définition de mot de passe a été envoyé.');
    }

    public function sendPasswordReset(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $sent = $this->users->sendPasswordReset($user);

        return $sent
            ? back()->with('status', 'Lien de réinitialisation envoyé.')
            : back()->withErrors(['user' => 'Le lien n\'a pas pu être envoyé (trop de demandes récentes, réessayez dans une minute).']);
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if ($user->is(Auth::user())) {
            return back()->withErrors(['user' => 'Vous ne pouvez pas désactiver votre propre compte.']);
        }

        $this->users->deactivate($user, Auth::user());

        return back()->with('status', 'Compte désactivé.');
    }

    public function reactivate(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->users->reactivate($user, Auth::user());

        return back()->with('status', 'Compte réactivé.');
    }
}
