<?php

namespace App\Domain\Students\Http\Controllers;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Students\Enums\CourseType;
use App\Domain\Students\Enums\DossierStatus;
use App\Domain\Students\Enums\LicenseCategory;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Exceptions\InvalidDossierTransition;
use App\Domain\Students\Http\Requests\StoreStudentRequest;
use App\Domain\Students\Http\Requests\UpdateDossierStatusRequest;
use App\Domain\Students\Http\Requests\UpdateStudentRequest;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Repositories\StudentRepositoryInterface;
use App\Domain\Students\Services\DossierStatusService;
use App\Domain\Students\Services\EnrollmentService;
use App\Domain\Students\Services\LifecycleService;
use App\Domain\Users\Services\UserManagementService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function __construct(
        private readonly StudentRepositoryInterface $students,
        private readonly EnrollmentService $enrollment,
        private readonly LifecycleService $lifecycle,
        private readonly DossierStatusService $dossier,
        private readonly AuditService $audit,
        private readonly UserManagementService $users,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Student::class);

        $filters = $request->only([
            'search', 'stage', 'license_category', 'course_type', 'instructor_id', 'registered_from', 'registered_to',
        ]);

        return view('students.index', [
            'students' => $this->students->paginate($filters),
            'filters' => $filters,
            'stages' => LifecycleStage::cases(),
            'licenseCategories' => LicenseCategory::cases(),
            'courseTypes' => CourseType::cases(),
            'instructors' => $this->instructors(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Student::class);

        return view('students.form', [
            'student' => new Student,
            'instructors' => $this->instructors(),
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $student = DB::transaction(function () use ($request) {
            $student = $this->enrollment->register($request->validated());

            $this->users->createAccount([
                'name' => $student->fullName(),
                'email' => $student->email,
                'role' => 'eleve',
                'student_id' => $student->id,
            ], Auth::user());

            return $student;
        });

        return redirect()->route('students.show', $student)
            ->with('status', 'Élève créé. Un lien de définition de mot de passe lui a été envoyé.');
    }

    public function show(Student $student): View
    {
        $this->authorize('view', $student);

        return view('students.show', [
            'student' => $student,
            'stages' => LifecycleStage::cases(),
        ]);
    }

    public function edit(Student $student): View
    {
        $this->authorize('update', $student);

        return view('students.form', [
            'student' => $student,
            'instructors' => $this->instructors(),
        ]);
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $this->enrollment->update($student, $request->validated());

        return redirect()->route('students.show', $student)
            ->with('status', 'Élève mis à jour.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->authorize('delete', $student);

        $this->audit->log('student.deleted', $student, $student->only(['first_name', 'last_name']), [], Auth::user());

        if ($student->user_id) {
            $this->users->deactivate(User::query()->findOrFail($student->user_id), Auth::user());
        }

        $this->students->delete($student);

        return redirect()->route('students.index')
            ->with('status', 'Élève supprimé.');
    }

    public function advanceStage(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('update', $student);

        $target = LifecycleStage::from($request->validate([
            'stage' => ['required', 'string'],
        ])['stage']);

        $this->lifecycle->transitionTo($student, $target);

        return redirect()->route('students.show', $student)
            ->with('status', 'Étape mise à jour.');
    }

    public function updateDossierStatus(UpdateDossierStatusRequest $request, Student $student): RedirectResponse
    {
        $target = DossierStatus::from($request->validated('dossier_status'));

        try {
            $this->dossier->transitionTo($student, $target);
        } catch (InvalidDossierTransition $e) {
            return back()->withErrors(['dossier_status' => $e->getMessage()]);
        }

        return redirect()->route('students.show', $student)
            ->with('status', 'Statut du dossier mis à jour.');
    }

    public function createAccount(Student $student): RedirectResponse
    {
        $this->authorize('update', $student);

        if ($student->user_id) {
            return back()->withErrors(['account' => 'Cet élève a déjà un compte.']);
        }

        if (! $student->email) {
            return back()->withErrors(['account' => 'Renseignez d\'abord une adresse e-mail pour cet élève.']);
        }

        $this->users->createAccount([
            'name' => $student->fullName(),
            'email' => $student->email,
            'role' => 'eleve',
            'student_id' => $student->id,
        ], Auth::user());

        return back()->with('status', 'Compte créé. Un lien de définition de mot de passe a été envoyé.');
    }

    private function instructors()
    {
        return User::role('moniteur')->active()->orderBy('name')->get();
    }
}
