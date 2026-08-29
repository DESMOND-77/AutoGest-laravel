# Combine Account Creation With Domain Records Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Stop "Comptes utilisateurs" from being able to create standalone `eleve` or `moniteur` accounts with no linked `Student`/`Instructor` record (the exact bug that produced an orphaned eleve user with no student, causing a 404 on `/eleve/dossier`). Instead, creating a student always creates its linked eleve account, creating an instructor always creates its linked moniteur account, and "Comptes utilisateurs" is reduced to admin-only account creation (it keeps listing/managing every role).

**Architecture:** `UserManagementService::createAccount()` already does everything an account creation needs (random password, `email_verified_at`, role assignment, optional `student_id` link, audit log, password-reset email) — it stays untouched and becomes an internal collaborator of `StudentController` and `InstructorController`, not just of `UserManagementController`. `EnrollmentService` (shared by the admin student form, the public self-registration flow, and CRM lead conversion) is **not** touched — those other two callers pass emails that may be absent or unverified, so account creation must live in `StudentController` only, not in the shared service.

**Tech Stack:** Laravel 12, PHP 8.5, Pest 3, Blade, Tailwind (Soft UI tokens), Spatie laravel-permission.

**Spec:** No separate spec document — this plan was scoped directly with the user via clarifying questions after diagnosing the orphaned-account bug (user id 3, `closenior@gmail.com`, `eleve` role with no `students` row).

## Global Constraints

- Never add account-creation logic to `EnrollmentService::register()` — it is shared by `PublicStudentRegistrationService` (already creates its own account beforehand and passes `user_id`) and `App\Domain\CRM\Services\LeadService::convert()` (passes a possibly-empty `email` from a `Lead`). Only `StudentController::store()` gets the new logic.
- `users` table has `UNIQUE(structure_id, email)` — every new account-creation validation rule must check uniqueness against `users` scoped to `Auth::user()->structure_id`, matching the existing `StoreUserRequest` pattern.
- `UserManagementService::createAccount()` signature and behavior do not change (still: random 32-char password nobody sees, `email_verified_at` set immediately, `Password::sendResetLink()`, audit log `user.created`).
- No arrow glyphs (`←`/`→`) anywhere in new UI copy — use `<x-icon name="chevron-left|chevron-right">` if a directional icon is ever needed.
- No destructive migrations. `students.email` stays nullable at the DB level; the "required" rule is enforced only in `StoreStudentRequest`.
- Every change must have a passing Pest test. Run `vendor/bin/pint --dirty --format agent` after PHP edits.

---

### Task 1: Auto-create the eleve account when an admin creates a student

**Files:**
- Modify: `app/Domain/Students/Http/Requests/StoreStudentRequest.php`
- Modify: `app/Domain/Students/Http/Controllers/StudentController.php`
- Modify: `resources/views/students/form.blade.php`
- Test: `tests/Feature/Students/StudentAccountCreationTest.php`

**Interfaces:**
- Consumes: `App\Domain\Users\Services\UserManagementService::createAccount(array $data, User $actor): User` — existing, unchanged. `$data` accepts `name`, `email`, `role`, optional `student_id`.
- Produces: nothing new consumed elsewhere.

- [ ] **Step 1: Make `email` required and unique against `users` in `StoreStudentRequest`**

Edit `app/Domain/Students/Http/Requests/StoreStudentRequest.php`. Replace the `email` rule and add the `Rule` import already present (it already imports `Illuminate\Validation\Rule` for `instructor_id`):

```php
'email' => [
    'required',
    'email',
    'max:150',
    Rule::unique('users')->where('structure_id', $this->user()->structure_id),
],
```

Add a `messages()` method so the duplicate-email case reads clearly (no other rule on this request currently needs a custom message):

```php
public function messages(): array
{
    return [
        'email.unique' => 'Un compte existe déjà avec cet e-mail pour votre auto-école.',
    ];
}
```

- [ ] **Step 2: Wire `UserManagementService` into `StudentController` and create the account after the student**

Edit `app/Domain/Students/Http/Controllers/StudentController.php`:

Add the import `use App\Domain\Users\Services\UserManagementService;` and add the dependency to the constructor:

```php
public function __construct(
    private readonly StudentRepositoryInterface $students,
    private readonly EnrollmentService $enrollment,
    private readonly LifecycleService $lifecycle,
    private readonly AuditService $audit,
    private readonly UserManagementService $users,
) {}
```

Replace `store()`:

```php
public function store(StoreStudentRequest $request): RedirectResponse
{
    $student = $this->enrollment->register($request->validated());

    $this->users->createAccount([
        'name' => $student->fullName(),
        'email' => $student->email,
        'role' => 'eleve',
        'student_id' => $student->id,
    ], Auth::user());

    return redirect()->route('students.show', $student)
        ->with('status', 'Élève créé. Un lien de définition de mot de passe lui a été envoyé.');
}
```

`Auth` is already imported in this file (used in `destroy()`).

- [ ] **Step 3: Make the email field required in the create/edit form and explain the account email**

Edit `resources/views/students/form.blade.php`. Find the email field block:

```blade
<div>
    <x-input-label for="email" value="E-mail" />
    <x-text-input id="email" type="email" name="email" class="block mt-1 w-full" :value="old('email', $student->email)" />
</div>
```

Replace with:

```blade
<div>
    <x-input-label for="email" value="E-mail" />
    <x-text-input id="email" type="email" name="email" class="block mt-1 w-full" :value="old('email', $student->email)" required />
    <x-input-error :messages="$errors->get('email')" class="mt-2" />
    @unless ($student->exists)
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Un compte élève sera créé avec cette adresse et recevra un lien pour définir son mot de passe.</p>
    @endunless
</div>
```

- [ ] **Step 4: Write the feature test**

Create `tests/Feature/Students/StudentAccountCreationTest.php`:

```php
<?php

use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
});

it('creates a linked eleve account and emails a password-reset link when an admin creates a student', function () {
    Notification::fake();

    $response = $this->actingAs($this->admin)->post(route('students.store'), [
        'first_name' => 'Awa',
        'last_name' => 'Diallo',
        'email' => 'awa.diallo@example.com',
        'license_category' => 'B',
        'course_type' => 'normal',
    ]);

    $student = Student::query()->where('email', 'awa.diallo@example.com')->firstOrFail();
    $response->assertRedirect(route('students.show', $student));

    expect($student->user_id)->not->toBeNull();

    $user = User::query()->findOrFail($student->user_id);
    expect($user->hasRole('eleve'))->toBeTrue();
    expect($user->name)->toBe('Awa Diallo');

    Notification::assertSentTo($user, ResetPassword::class);
});

it('rejects a student email that already belongs to another account in the same tenant', function () {
    User::factory()->create(['structure_id' => $this->structure->id, 'email' => 'taken@example.com']);

    $response = $this->actingAs($this->admin)->post(route('students.store'), [
        'first_name' => 'Awa',
        'last_name' => 'Diallo',
        'email' => 'taken@example.com',
        'license_category' => 'B',
        'course_type' => 'normal',
    ]);

    $response->assertSessionHasErrors('email');
    expect(Student::query()->where('email', 'taken@example.com')->exists())->toBeFalse();
});

it('allows the same email to be reused by a student in a different tenant', function () {
    $otherStructure = Structure::factory()->create();
    User::factory()->create(['structure_id' => $otherStructure->id, 'email' => 'shared@example.com']);

    $this->actingAs($this->admin)->post(route('students.store'), [
        'first_name' => 'Awa',
        'last_name' => 'Diallo',
        'email' => 'shared@example.com',
        'license_category' => 'B',
        'course_type' => 'normal',
    ])->assertRedirect();

    expect(Student::query()->where('email', 'shared@example.com')->where('structure_id', $this->structure->id)->exists())->toBeTrue();
});
```

- [ ] **Step 5: Run the test, then the full Students suite**

Run: `php artisan test --compact tests/Feature/Students/StudentAccountCreationTest.php`
Expected: 3 passed.

Then run: `php artisan test --compact --filter=Students` to confirm `PublicStudentRegistrationTest` and the dossier tests are unaffected (they use `EnrollmentService` through `PublicStudentRegistrationService`, untouched by this task).
Expected: all passed.

- [ ] **Step 6: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Students/Http/Requests/StoreStudentRequest.php \
        app/Domain/Students/Http/Controllers/StudentController.php \
        resources/views/students/form.blade.php \
        tests/Feature/Students/StudentAccountCreationTest.php
git commit -m "feat(students): create the linked eleve account when an admin creates a student"
```

---

### Task 2: Retrofit path for existing students without an account

**Files:**
- Modify: `app/Domain/Students/Http/Controllers/StudentController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/students/show.blade.php`
- Test: `tests/Feature/Students/StudentAccountCreationTest.php` (append)

**Interfaces:**
- Consumes: `UserManagementService::createAccount()` (same as Task 1); `StudentController::$users` (added in Task 1).
- Produces: route `students.create-account` (POST `students/{student}/account`).

- [ ] **Step 1: Add the route**

Edit `routes/web.php`. In the same `Route::middleware(['auth', 'role:admin|moniteur'])` group that defines `Route::resource('students', ...)` and `students/{student}/stage` (around line 79-81), add:

```php
Route::post('students/{student}/account', [StudentController::class, 'createAccount'])->name('students.create-account');
```

- [ ] **Step 2: Add the controller action**

Edit `app/Domain/Students/Http/Controllers/StudentController.php`. Add a new method, placed after `advanceStage()`:

```php
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
```

The `StoreUserRequest`-level tenant/uniqueness checks don't apply here since the student is already tenant-scoped and its email doesn't need re-validating; a same-tenant duplicate would surface as a DB constraint violation, which cannot happen here because a `Student` row's `email` field is independent of `users.email` and this action is only reachable for a student whose `user_id` is still null — if that email collides with an existing `users` row in the same tenant, let it raise (this mirrors how `UserManagementService::createAccount()` already has no explicit duplicate guard for its other callers).

- [ ] **Step 3: Replace the "Créer un compte" link in the student profile with a POST form**

Edit `resources/views/students/show.blade.php`. Replace:

```blade
@can('update', $student)
    @if (! $student->user_id)
        <a href="{{ route('settings.users.index', ['student' => $student->id]) }}" class="text-sm text-content-secondary hover:text-primary transition">Créer un compte</a>
    @endif
@endcan
```

With:

```blade
@can('update', $student)
    @if (! $student->user_id)
        <form method="POST" action="{{ route('students.create-account', $student) }}">
            @csrf
            <button type="submit" class="text-sm text-content-secondary hover:text-primary transition">Créer un compte</button>
        </form>
    @endif
@endcan
```

- [ ] **Step 4: Write the feature test**

Append to `tests/Feature/Students/StudentAccountCreationTest.php`:

```php
it('lets an admin create an account for an existing student with no login yet', function () {
    Notification::fake();
    $student = Student::factory()->create(['structure_id' => $this->structure->id, 'email' => 'legacy@example.com']);

    $this->actingAs($this->admin)
        ->post(route('students.create-account', $student))
        ->assertRedirect();

    $student->refresh();
    expect($student->user_id)->not->toBeNull();
    Notification::assertSentTo(User::query()->findOrFail($student->user_id), ResetPassword::class);
});

it('refuses to create a second account for a student that already has one', function () {
    $existingUser = User::factory()->create(['structure_id' => $this->structure->id]);
    $student = Student::factory()->create(['structure_id' => $this->structure->id, 'user_id' => $existingUser->id]);

    $this->actingAs($this->admin)
        ->post(route('students.create-account', $student))
        ->assertSessionHasErrors('account');
});

it('refuses to create an account for a student with no email on file', function () {
    $student = Student::factory()->create(['structure_id' => $this->structure->id, 'email' => null]);

    $this->actingAs($this->admin)
        ->post(route('students.create-account', $student))
        ->assertSessionHasErrors('account');

    expect($student->fresh()->user_id)->toBeNull();
});
```

- [ ] **Step 5: Run the tests**

Run: `php artisan test --compact tests/Feature/Students/StudentAccountCreationTest.php`
Expected: 6 passed (3 from Task 1 + 3 new).

- [ ] **Step 6: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Students/Http/Controllers/StudentController.php \
        routes/web.php \
        resources/views/students/show.blade.php \
        tests/Feature/Students/StudentAccountCreationTest.php
git commit -m "feat(students): let an admin create the missing account for an existing student"
```

---

### Task 3: Combine moniteur account creation with instructor profile creation

**Files:**
- Modify: `app/Domain/Instructors/Http/Requests/StoreInstructorRequest.php`
- Modify: `app/Domain/Instructors/Http/Controllers/InstructorController.php`
- Modify: `resources/views/instructors/index.blade.php`
- Modify: `tests/Feature/Instructors/InstructorControllerTest.php`

**Interfaces:**
- Consumes: `UserManagementService::createAccount()` (unchanged).
- Produces: nothing new consumed elsewhere.

- [ ] **Step 1: Replace `user_id` with `name`+`email` in `StoreInstructorRequest`**

Replace the full content of `app/Domain/Instructors/Http/Requests/StoreInstructorRequest.php`:

```php
<?php

namespace App\Domain\Instructors\Http\Requests;

use App\Domain\Instructors\Models\Instructor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInstructorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Instructor::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users')->where('structure_id', $this->user()->structure_id),
            ],
            'license_number' => ['nullable', 'string', 'max:50'],
            'specialties' => ['nullable', 'array'],
            'specialties.*' => ['string', 'max:100'],
            'hire_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Un compte existe déjà avec cet e-mail pour votre auto-école.',
        ];
    }
}
```

- [ ] **Step 2: Create the moniteur account inside `InstructorController::store()`**

Edit `app/Domain/Instructors/Http/Controllers/InstructorController.php`. Add imports `use App\Domain\Users\Services\UserManagementService;` and `use Illuminate\Support\Facades\Auth;`, add the dependency, and replace `store()`:

```php
public function __construct(
    private readonly InstructorRepositoryInterface $instructors,
    private readonly UserManagementService $users,
) {}
```

```php
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
```

- [ ] **Step 3: Replace the "Utilisateur (id)" field with Nom/E-mail in the create form**

Edit `resources/views/instructors/index.blade.php`. Add an error banner right after the `session('status')` block:

```blade
@if ($errors->any())
    <x-alert variant="danger">{{ $errors->first() }}</x-alert>
@endif
```

Replace the `user_id` field:

```blade
<div>
    <x-input-label for="user_id" value="Utilisateur (id)" />
    <x-text-input id="user_id" type="number" name="user_id" class="block mt-1 w-full" required />
</div>
```

With:

```blade
<div>
    <x-input-label for="name" value="Nom complet" />
    <x-text-input id="name" name="name" class="block mt-1 w-full" required />
</div>
<div>
    <x-input-label for="email" value="E-mail" />
    <x-text-input id="email" type="email" name="email" class="block mt-1 w-full" required />
</div>
```

Below the form (inside the same `<x-card>`, after the closing `</form>`), add the same "no password chosen here" note used on `resources/views/users/index.blade.php`:

```blade
<p class="text-xs text-content-muted mt-3">
    Le nouveau compte moniteur reçoit un e-mail avec un lien pour définir son mot de passe.
</p>
```

- [ ] **Step 4: Update the existing test to the new fields**

Edit `tests/Feature/Instructors/InstructorControllerTest.php`. Replace the first test:

```php
it('lets an admin create an instructor profile for a moniteur user', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($this->admin)
        ->post(route('instructors.store'), [
            'user_id' => $moniteur->id,
            'license_number' => 'MON-0001',
            'hire_date' => '2024-01-15',
        ])
        ->assertRedirect(route('instructors.index'));

    expect(Instructor::query()->where('user_id', $moniteur->id)->exists())->toBeTrue();
});
```

With:

```php
it('lets an admin create a moniteur account and its instructor profile together', function () {
    Notification::fake();

    $this->actingAs($this->admin)
        ->post(route('instructors.store'), [
            'name' => 'Jean Moniteur',
            'email' => 'jean.moniteur@example.com',
            'license_number' => 'MON-0001',
            'hire_date' => '2024-01-15',
        ])
        ->assertRedirect(route('instructors.index'));

    $user = User::query()->where('email', 'jean.moniteur@example.com')->firstOrFail();
    expect($user->hasRole('moniteur'))->toBeTrue();
    expect(Instructor::query()->where('user_id', $user->id)->where('license_number', 'MON-0001')->exists())->toBeTrue();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('rejects an instructor email that already belongs to another account in the same tenant', function () {
    User::factory()->create(['structure_id' => $this->structure->id, 'email' => 'taken@example.com']);

    $this->actingAs($this->admin)
        ->post(route('instructors.store'), [
            'name' => 'Jean Moniteur',
            'email' => 'taken@example.com',
        ])
        ->assertSessionHasErrors('email');

    expect(Instructor::query()->count())->toBe(0);
});
```

Add the needed imports at the top of the file (after the existing `use` statements):

```php
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
```

- [ ] **Step 5: Run the tests**

Run: `php artisan test --compact tests/Feature/Instructors/InstructorControllerTest.php`
Expected: 3 passed (the untouched "lets an admin list instructors..." test + 2 new/replaced).

- [ ] **Step 6: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Instructors/Http/Requests/StoreInstructorRequest.php \
        app/Domain/Instructors/Http/Controllers/InstructorController.php \
        resources/views/instructors/index.blade.php \
        tests/Feature/Instructors/InstructorControllerTest.php
git commit -m "feat(instructors): create the linked moniteur account when an admin adds an instructor"
```

---

### Task 4: Restrict "Comptes utilisateurs" account creation to admin only

**Files:**
- Modify: `app/Domain/Users/Http/Requests/StoreUserRequest.php`
- Modify: `app/Domain/Users/Http/Controllers/UserManagementController.php`
- Modify: `resources/views/users/index.blade.php`
- Modify: `tests/Feature/Users/UserManagementTest.php`
- Modify: `tests/Unit/Users/UserManagementServiceTest.php` (no behavior change, listed for confirmation only — see Step 5)

**Interfaces:**
- Consumes: nothing new.
- Produces: nothing new consumed elsewhere. `UserManagementService::createAccount()` keeps accepting any role — this task only narrows what the *HTTP form* is allowed to submit.

- [ ] **Step 1: Restrict the role rule to `admin` and drop `student_id`**

Edit `app/Domain/Users/Http/Requests/StoreUserRequest.php`. Replace the `rules()` body:

```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:150'],
        'email' => [
            'required',
            'email',
            'max:150',
            Rule::unique('users')->where('structure_id', Auth::user()->structure_id),
        ],
    ];
}
```

(`role` and `student_id` are removed entirely — the controller now hardcodes `role: 'admin'`.)

- [ ] **Step 2: Hardcode `role: 'admin'` in the controller, drop the unlinked-students plumbing**

Edit `app/Domain/Users/Http/Controllers/UserManagementController.php`. Replace `store()`:

```php
public function store(StoreUserRequest $request): RedirectResponse
{
    $this->users->createAccount([
        ...$request->validated(),
        'role' => 'admin',
    ], Auth::user());

    return redirect()->route('settings.users.index')
        ->with('status', 'Compte créé. Un lien de définition de mot de passe a été envoyé.');
}
```

In `index()`, remove the `$preselectedStudent` computation and the `'unlinkedStudents'` / `'preselectedStudent'` view keys — the role-tabs listing (`roleFilter`, `roleCounts`) stays untouched since accounts of every role are still listed and managed here. The `Student` import and `$request->filled('student')` block can be deleted along with it:

```php
public function index(Request $request): View
{
    $this->authorize('viewAny', User::class);

    $roleFilter = $request->query('role');
    $roleFilter = in_array($roleFilter, ['admin', 'moniteur', 'eleve'], true) ? $roleFilter : null;

    $query = User::query()->with('roles')->orderBy('name');

    if ($roleFilter) {
        $query->role($roleFilter);
    }

    return view('users.index', [
        'users' => $query->paginate(20)->withQueryString(),
        'roleFilter' => $roleFilter,
        'roleCounts' => collect(['admin', 'moniteur', 'eleve'])
            ->mapWithKeys(fn (string $role) => [$role => User::role($role)->count()]),
    ]);
}
```

Remove the now-unused `use App\Domain\Students\Models\Student;` import.

- [ ] **Step 3: Simplify the create form to admin-only, drop the role/student pickers**

Edit `resources/views/users/index.blade.php`. Replace the `<x-card>` "Créer un compte" block:

```blade
<x-card>
    <h2 class="text-sm font-semibold text-content mb-3">Créer un compte administrateur</h2>
    <form method="POST" action="{{ route('settings.users.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @csrf
        <div>
            <x-input-label for="name" value="Nom complet" />
            <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name')" required />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" type="email" name="email" class="block mt-1 w-full" :value="old('email')" required />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>
        <div class="sm:col-span-2">
            <x-primary-button>Créer le compte</x-primary-button>
        </div>
    </form>
    <p class="text-xs text-content-muted mt-3">
        Le nouveau compte reçoit un e-mail avec un lien pour définir son mot de passe — aucun mot de passe n'est choisi ici.
        Les comptes élève se créent depuis la fiche d'un élève, et les comptes moniteur depuis <a href="{{ route('instructors.index') }}" class="text-primary hover:underline">Moniteurs</a>.
    </p>
</x-card>
```

This removes the `role` select and the `student_id` select entirely, and the `preselectedStudent`/`unlinkedStudents` references that no longer exist in the view data.

- [ ] **Step 4: Update `UserManagementTest`**

Edit `tests/Feature/Users/UserManagementTest.php`. Replace the first test (multi-role creation loop):

```php
it('lets an admin create an eleve account, an admin account, and a moniteur account from one screen', function () {
```

...with an admin-only version, and delete the two eleve/student-linking tests that no longer apply (`'links a new eleve account...'`, `'shows the students-without-accounts list...'`, and the cross-tenant `student_id` test). Replace the whole file's role-creation section with:

```php
it('lets an admin create an admin account', function () {
    Notification::fake();

    $response = $this->actingAs($this->admin)->post(route('settings.users.store'), [
        'name' => 'Test admin',
        'email' => 'admin2@example.com',
    ]);

    $response->assertRedirect(route('settings.users.index'));

    $user = User::query()->where('email', 'admin2@example.com')->firstOrFail();
    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->structure_id)->toBe($this->structure->id);

    Notification::assertSentTo($user, ResetPassword::class);
});

it('ignores an invalid role query param instead of erroring', function () {
    $this->actingAs($this->admin)->get(route('settings.users.index', ['role' => 'not-a-real-role']))
        ->assertOk()
        ->assertSee($this->admin->name);
});
```

Remove the now-obsolete `use App\Domain\Students\Models\Student;` import if nothing else in the file uses `Student` after these deletions (check remaining tests in the file first — none of the retained tests reference `Student`).

Also update the policy-denial test at the bottom, which posts `role: 'eleve'`:

```php
it('denies a moniteur from accessing the account-management screen entirely', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->get(route('settings.users.index'))->assertForbidden();
    $this->actingAs($moniteur)->post(route('settings.users.store'), [
        'name' => 'Test', 'email' => 'x@example.com',
    ])->assertForbidden();
});
```

- [ ] **Step 5: Confirm `UserManagementServiceTest` still passes unmodified**

`UserManagementServiceTest` calls `UserManagementService::createAccount()` directly (not through the HTTP form), including its `role: 'eleve'` + `student_id` test — that is still valid because the service itself is unchanged; only the HTTP layer is restricted. No edits needed to this file. Run it to confirm:

Run: `php artisan test --compact tests/Unit/Users/UserManagementServiceTest.php`
Expected: all passed, unchanged.

- [ ] **Step 6: Run the full Users suite**

Run: `php artisan test --compact tests/Feature/Users/UserManagementTest.php`
Expected: all passed.

- [ ] **Step 7: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Users/Http/Requests/StoreUserRequest.php \
        app/Domain/Users/Http/Controllers/UserManagementController.php \
        resources/views/users/index.blade.php \
        tests/Feature/Users/UserManagementTest.php
git commit -m "fix(users): restrict Comptes utilisateurs account creation to admin only"
```

---

### Task 5: Whole-branch verification

**Files:** none (verification only).

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: all passed, including `tests/Architecture/DomainBoundariesTest.php` (no new cross-domain dependency was introduced — `StudentController` and `InstructorController` already depend on `Users`-domain-adjacent services, since `UserManagementController` already lived in `App\Domain\Users` and was already reachable; confirm the architecture test still passes rather than assuming it).

- [ ] **Step 2: Manually confirm the original bug is fixed**

Using Tinker or the admin UI, create a new student with an email, then check that a `Student` row and a `User` row (role `eleve`) both exist and are linked, and that visiting `/eleve/dossier` as that new user (after setting its password) returns 200, not 404.
