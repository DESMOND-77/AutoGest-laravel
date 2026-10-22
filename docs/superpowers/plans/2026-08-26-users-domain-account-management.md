# Users Domain — Unified Account Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give an admin a single screen (`/settings/users`) to list every account in their tenant (admin/moniteur/eleve), create a new account of any of those three roles, trigger a standard password-reset email, and deactivate/reactivate an account — closing the gap identified in `docs/audit/roadmap.md` (étape 12, TECH-01) and the `Promptset/01-ecran-utilisateurs.md` prompt this plan implements.

**Architecture:** A thin new `App\Domain\Users` domain that owns account-management *logic*, not a new model — `App\Models\User` (already `BelongsToTenant` + Spatie `HasRoles`) stays the one source of truth for identity. Research (see below) confirmed two important gaps this plan closes: (1) `InstructorController::store()` already assumes the target `User` exists — there is currently **no** code path anywhere in the app that creates a `moniteur` user account, only that attaches an `Instructor` profile to one; (2) an admin-created `Student` (via `/students`) never gets a linked `User` — only the public self-registration flow creates User+Student together. This plan's `UserManagementService::createAccount()` becomes the *one* place a `User` row is ever created by staff, for any of the three roles, and the existing `/instructors` screen keeps owning the professional-profile layer (license, specialties, availabilities) on top of a `moniteur` account this new screen provisions.

Every account this feature creates gets a cryptographically random, never-revealed password and an immediate Laravel-standard password-reset email (`Password::sendResetLink()`) — nobody, including the admin, ever sees or sets an initial password by hand. This also resolves what would otherwise be a dead end for an admin-created `eleve` account: rather than inventing a second OTP flow for staff-created students, `email_verified_at` is set immediately at creation (an admin vouching for the account, combined with the recipient having to click the reset-password email to ever log in, together serve the same "does this person own this inbox" purpose the self-registration OTP exists for) — so the account never hits the `otp.verified` middleware's redirect-to-nowhere.

**Tech Stack:** Laravel 12, PHP 8.5, Pest 3, Blade + Tailwind (Soft UI tokens already in the app), `spatie/laravel-permission` (already installed, `RoleSeeder`), Laravel's built-in password-broker (`Illuminate\Support\Facades\Password`, already wired via Breeze).

**Spec:** `Promptset/01-ecran-utilisateurs.md`

## Global Constraints

- `structure_id` isolation on every query/policy — the most frequently-reintroduced bug class per `docs/audit/multi-tenancy-audit.md` (MT-01, MT-03, MT-05). Every new query must go through `BelongsToTenant`'s global scope (i.e. run while `TenantContext` is set, which it always is on an authenticated `role:admin` route via `ResolveTenant`) or explicitly re-check `structure_id`.
- Role assignment goes through `spatie/laravel-permission`'s `assignRole()` — never a raw `role` column (there isn't one; don't add one).
- `/instructors` stays the only place an `Instructor` profile (license/specialties/availabilities) is created or edited. This plan's screen creates the underlying `User` account only — it must never write to the `instructors` table.
- Follow `laravel-best-practices`: Policies gate authorization, FormRequests gate validation, controllers stay thin (orchestration only, delegate to `UserManagementService`).
- Run `vendor/bin/pint --dirty --format agent` after any PHP file change.
- `php artisan test --compact --filter=UserManagement` after the relevant task; full suite (`php artisan test --compact`) at the end of the final task.
- New route group nests under the existing `settings.` prefix (`role:admin`), matching `settings.student-registration.*` and `settings.document-types.*` — do not invent a new top-level prefix.

## Known residual risk — documented, not fixed by this plan

`password_reset_tokens` is keyed by plain `email` (`email` is the table's primary key — see `database/migrations/0001_01_01_000000_create_users_table.php`), and `users` is only unique on the pair `(structure_id, email)` — two different tenants can legitimately have a user with the same email. Laravel's password-reset broker re-resolves the target user by email alone when the link is clicked (a public, unauthenticated route with no `TenantContext` active), so if the same email exists in two tenants, which tenant's account actually gets its password changed is ambiguous. This is a **pre-existing** gap in the app's already-shipped Breeze forgot-password flow (already true today for any two tenants sharing an admin's email) — this plan does not redesign Laravel's password broker to fix it, since that's a substantial, risky, cross-cutting change well beyond "add an admin account-management screen." Task 8's documentation must call this out explicitly as a residual risk, matching how `docs/features/student-public-registration.md` documents its own IP-throttling caveat.

---

## File Structure

**New files**
- `database/migrations/xxxx_add_is_active_to_users_table.php`
- `app/Domain/Users/Policies/UserPolicy.php`
- `app/Domain/Users/Services/UserManagementService.php`
- `app/Domain/Users/Http/Requests/StoreUserRequest.php`
- `app/Domain/Users/Http/Controllers/UserManagementController.php`
- `app/Domain/Users/Http/Middleware/EnsureUserIsActive.php`
- `resources/views/users/index.blade.php`
- `tests/Feature/Users/UserManagementTest.php`

**Modified files**
- `database/factories/UserFactory.php` (add an `inactive()` state)
- `app/Http/Requests/Auth/LoginRequest.php` (deny login for a deactivated account)
- `bootstrap/app.php` (register `EnsureUserIsActive` on the `web` group, alongside `ResolveTenant`)
- `app/Providers/AppServiceProvider.php` (`Gate::policy(User::class, UserPolicy::class)`)
- `routes/web.php` (`settings.users.*` group)
- `resources/views/layouts/partials/sidebar-nav.blade.php` (nav link, Administration block)
- `resources/views/students/show.blade.php` ("Créer un compte" action when `$student->user_id` is null)
- `docs/features/student-public-registration.md` or a new `docs/features/user-account-management.md` (Task 8 decides which; a new file is cleaner since this isn't really about student registration)

---

## Task 1: `is_active` column, `User` model updates, factory state

**Files:**
- Create: `database/migrations/xxxx_add_is_active_to_users_table.php`
- Modify: `app/Models/User.php`
- Modify: `database/factories/UserFactory.php`
- Test: `tests/Unit/Users/UserModelTest.php`

**Interfaces:**
- Produces: `User::$fillable` includes `is_active`; `User::casts()` casts it to `boolean`; `User::scopeActive(Builder $query): Builder` (mirrors `RequiredDocumentType::scopeActive()`); `UserFactory::inactive(): static` state.

- [ ] **Step 1: Create the migration**

```bash
php artisan make:migration add_is_active_to_users_table --no-interaction
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
```

- [ ] **Step 2: Update the `User` model**

Edit `app/Models/User.php`:

```php
<?php

namespace App\Models;

use App\Domain\Tenancy\Models\Structure;
use App\Support\BelongsToTenant;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use BelongsToTenant, HasFactory, HasRoles, Notifiable;

    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    protected $fillable = ['structure_id', 'name', 'email', 'password', 'is_active'];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(Structure::class);
    }

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
```

(If `newFactory()`/imports differ slightly from the file's current exact state, keep the rest of the file's existing content — only add `is_active` to `$fillable`, the cast, and the new `scopeActive()` method; do not reformat unrelated lines.)

- [ ] **Step 3: Add the factory state**

Edit `database/factories/UserFactory.php`, add after `unverified()`:

```php
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
```

- [ ] **Step 4: Write and run a small model test**

```php
<?php

use App\Domain\Tenancy\Models\Structure;
use App\Models\User;

it('defaults new users to active', function () {
    $user = User::factory()->create(['structure_id' => Structure::factory()->create()->id]);

    expect($user->is_active)->toBeTrue();
});

it('scopes to only active users', function () {
    $structure = Structure::factory()->create();
    User::factory()->create(['structure_id' => $structure->id]);
    User::factory()->inactive()->create(['structure_id' => $structure->id]);

    expect(User::query()->active()->count())->toBe(1);
});
```

```bash
php artisan migrate --no-interaction
php artisan test --compact --filter=UserModelTest
```

Expected: PASS.

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations app/Models/User.php database/factories/UserFactory.php tests/Unit/Users/UserModelTest.php
git commit -m "feat(users): add is_active column to users"
```

---

## Task 2: Block login and kill active sessions for a deactivated account

**Files:**
- Modify: `app/Http/Requests/Auth/LoginRequest.php`
- Create: `app/Domain/Users/Http/Middleware/EnsureUserIsActive.php`
- Modify: `bootstrap/app.php`
- Test: `tests/Feature/Auth/DeactivatedAccountTest.php`

**Interfaces:**
- Consumes: `User::$is_active` (Task 1).
- Produces: middleware alias `active` is NOT needed — this is appended globally to the `web` group like `ResolveTenant`, not opt-in per route.

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
});

it('refuses login for a deactivated account even with the correct password', function () {
    $user = User::factory()->inactive()->create([
        'structure_id' => $this->structure->id,
        'password' => Hash::make('correct-password'),
    ]);
    $user->assignRole('admin');

    $response = $this->post('/login', ['email' => $user->email, 'password' => 'correct-password']);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('logs out an already-authenticated user the moment their account is deactivated', function () {
    $user = User::factory()->create(['structure_id' => $this->structure->id]);
    $user->assignRole('admin');

    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    $user->update(['is_active' => false]);

    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
});
```

- [ ] **Step 2: Run it, confirm it fails**

```bash
php artisan test --compact --filter=DeactivatedAccountTest
```

Expected: FAIL — login still succeeds for an inactive user, and the second test 500s or passes through since nothing checks `is_active` on an existing session yet.

- [ ] **Step 3: Update `LoginRequest::authenticate()`**

Edit `app/Http/Requests/Auth/LoginRequest.php`, change the `Auth::attempt(...)` line:

```php
        if (! Auth::attempt($this->only('email', 'password') + ['is_active' => true], $this->boolean('remember'))) {
```

Laravel's `EloquentUserProvider` builds its credential query from every key in the array except `password`, so this adds a plain `WHERE is_active = 1` to the same query that already checks email — no separate lookup, no timing difference between "wrong password" and "deactivated" (both fall through to the same generic `trans('auth.failed')` message, which is correct: a deactivated account shouldn't reveal *why* login failed any more precisely than a wrong password does).

- [ ] **Step 4: Create the middleware**

```bash
php artisan make:middleware EnsureUserIsActive --no-interaction
```

Move it to `app/Domain/Users/Http/Middleware/EnsureUserIsActive.php` (matching `ResolveTenant`'s domain-namespaced location) and replace its contents:

```php
<?php

namespace App\Domain\Users\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Appended globally to the `web` group (like ResolveTenant) so a session
 * that was valid when it started still gets cut off the moment an admin
 * deactivates the account mid-session — LoginRequest's is_active check
 * alone only stops a *future* login attempt, not an already-open one.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        return $next($request);
    }
}
```

- [ ] **Step 5: Register it in `bootstrap/app.php`**

```php
use App\Domain\Users\Http\Middleware\EnsureUserIsActive;
```

```php
        $middleware->appendToGroup('web', ResolveTenant::class);
        $middleware->appendToGroup('web', EnsureUserIsActive::class);
```

(Order doesn't matter between these two — `EnsureUserIsActive` only reads `$request->user()`, it doesn't depend on `TenantContext`.)

- [ ] **Step 6: Run the tests, confirm they pass**

```bash
php artisan test --compact --filter=DeactivatedAccountTest
```

Expected: PASS (2/2).

- [ ] **Step 7: Run the broader auth suite to catch any regression, then Pint + commit**

```bash
php artisan test --compact tests/Feature/Auth
vendor/bin/pint --dirty --format agent
git add app/Http/Requests/Auth/LoginRequest.php app/Domain/Users/Http/Middleware/EnsureUserIsActive.php bootstrap/app.php tests/Feature/Auth/DeactivatedAccountTest.php
git commit -m "feat(users): block login and kill active sessions for a deactivated account"
```

---

## Task 3: `UserPolicy`

**Files:**
- Create: `app/Domain/Users/Policies/UserPolicy.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Unit/Users/UserPolicyTest.php`

**Interfaces:**
- Produces: `UserPolicy::viewAny(User $user): bool`, `::create(User $user): bool`, `::update(User $user, User $target): bool` (the ability name `update` is reused for both "send password reset" and "deactivate/reactivate" — there's no meaningful difference in who's allowed to do either, so one ability keeps this simple rather than inventing `resetPassword`/`deactivate`/`reactivate` abilities that would all have identical bodies).

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
});

it('lets an admin view and manage users in their own tenant', function () {
    $target = User::factory()->create(['structure_id' => $this->structure->id]);

    expect($this->admin->can('viewAny', User::class))->toBeTrue();
    expect($this->admin->can('create', User::class))->toBeTrue();
    expect($this->admin->can('update', $target))->toBeTrue();
});

it('denies an admin from managing another tenant\'s users', function () {
    $otherStructure = Structure::factory()->create();
    $target = User::factory()->create(['structure_id' => $otherStructure->id]);

    expect($this->admin->can('update', $target))->toBeFalse();
});

it('denies a non-admin role entirely', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    expect($moniteur->can('viewAny', User::class))->toBeFalse();
    expect($moniteur->can('create', User::class))->toBeFalse();
});
```

- [ ] **Step 2: Run it, confirm it fails**

```bash
php artisan test --compact --filter=UserPolicyTest
```

Expected: FAIL — `UserPolicy` doesn't exist / isn't registered.

- [ ] **Step 3: Create the policy**

```php
<?php

namespace App\Domain\Users\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, User $target): bool
    {
        return $user->hasRole('admin') && $target->structure_id === $user->structure_id;
    }
}
```

- [ ] **Step 4: Register it**

Edit `app/Providers/AppServiceProvider.php`:

```php
use App\Domain\Users\Policies\UserPolicy;
```

```php
        Gate::policy(User::class, UserPolicy::class);
```

(`App\Models\User::class` is likely already imported for other purposes in this file — reuse the existing `use App\Models\User;` import if present, don't duplicate it.)

- [ ] **Step 5: Run, confirm PASS; Pint; commit**

```bash
php artisan test --compact --filter=UserPolicyTest
vendor/bin/pint --dirty --format agent
git add app/Domain/Users/Policies/UserPolicy.php app/Providers/AppServiceProvider.php tests/Unit/Users/UserPolicyTest.php
git commit -m "feat(users): add UserPolicy, tenant-scoped admin-only account management"
```

---

## Task 4: `UserManagementService`

**Files:**
- Create: `app/Domain/Users/Services/UserManagementService.php`
- Test: `tests/Unit/Users/UserManagementServiceTest.php`

**Interfaces:**
- Consumes: `App\Domain\Audit\Services\AuditService` (existing, injected), `Illuminate\Support\Facades\Password`.
- Produces: `UserManagementService::createAccount(array $data, User $actor): User` (`$data` keys: `name`, `email`, `role` — one of `admin`/`moniteur`/`eleve` — and optional `student_id`), `::deactivate(User $target, User $actor): void`, `::reactivate(User $target, User $actor): void`, `::sendPasswordReset(User $target): void`. These four method names/signatures are relied on verbatim by Task 5's controller.

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Users\Services\UserManagementService;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    TenantContext::set($this->structure);
    $this->actor = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->actor->assignRole('admin');
    $this->service = app(UserManagementService::class);
});

afterEach(function () {
    TenantContext::clear();
});

it('creates an account with a role, marks the email verified, and sends a password-reset link', function () {
    Notification::fake();

    $user = $this->service->createAccount([
        'name' => 'Jean Moniteur',
        'email' => 'jean@example.com',
        'role' => 'moniteur',
    ], $this->actor);

    expect($user->structure_id)->toBe($this->structure->id);
    expect($user->hasRole('moniteur'))->toBeTrue();
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->is_active)->toBeTrue();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('never lets anyone learn the generated password', function () {
    $user = $this->service->createAccount([
        'name' => 'Jean Moniteur',
        'email' => 'jean2@example.com',
        'role' => 'moniteur',
    ], $this->actor);

    expect(\Illuminate\Support\Facades\Hash::check('password', $user->password))->toBeFalse();
});

it('links a newly created eleve account to an existing, unlinked student', function () {
    $student = Student::factory()->create(['structure_id' => $this->structure->id]);
    expect($student->user_id)->toBeNull();

    $user = $this->service->createAccount([
        'name' => 'Awa Eleve',
        'email' => 'awa@example.com',
        'role' => 'eleve',
        'student_id' => $student->id,
    ], $this->actor);

    expect($student->fresh()->user_id)->toBe($user->id);
});

it('logs the account creation to the audit trail', function () {
    $user = $this->service->createAccount([
        'name' => 'Jean Moniteur',
        'email' => 'jean3@example.com',
        'role' => 'moniteur',
    ], $this->actor);

    $log = AuditLog::query()->where('auditable_type', $user->getMorphClass())->where('auditable_id', $user->id)->first();

    expect($log)->not->toBeNull();
    expect($log->action)->toBe('user.created');
});

it('deactivates and reactivates an account, logging both to the audit trail', function () {
    $target = User::factory()->create(['structure_id' => $this->structure->id]);

    $this->service->deactivate($target, $this->actor);
    expect($target->fresh()->is_active)->toBeFalse();

    $this->service->reactivate($target, $this->actor);
    expect($target->fresh()->is_active)->toBeTrue();

    $logs = AuditLog::query()->where('auditable_type', $target->getMorphClass())->where('auditable_id', $target->id)->pluck('action');
    expect($logs)->toContain('user.deactivated', 'user.reactivated');
});

it('sends a password-reset link on demand for an existing account', function () {
    Notification::fake();
    $target = User::factory()->create(['structure_id' => $this->structure->id]);

    $this->service->sendPasswordReset($target);

    Notification::assertSentTo($target, ResetPassword::class);
});
```

- [ ] **Step 2: Run it, confirm it fails**

```bash
php artisan test --compact --filter=UserManagementServiceTest
```

Expected: FAIL — class doesn't exist.

- [ ] **Step 3: Implement the service**

```php
<?php

namespace App\Domain\Users\Services;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Students\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * The only place a User row is ever created by staff, for any of the three
 * roles this app has (admin/moniteur/eleve) — /instructors keeps owning the
 * Instructor *profile* (license/specialties/availabilities) on top of a
 * moniteur account this service provisions; it never touches the
 * `instructors` table itself.
 *
 * Every account gets a random, 32-character password nobody — not even the
 * creating admin — ever sees, plus an immediate standard Laravel
 * password-reset email. email_verified_at is set immediately: an admin
 * vouching for an account, combined with the recipient having to click the
 * reset-password link before they can ever log in, together serve the same
 * "does this person own this inbox" purpose the self-registration OTP flow
 * exists for — so an admin-created eleve account never gets stuck behind
 * `otp.verified` with no OTP ever having been sent.
 */
class UserManagementService
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array{name: string, email: string, role: string, student_id?: int|null}  $data
     */
    public function createAccount(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(Str::password(32)),
                'email_verified_at' => now(),
            ]);

            $user->assignRole($data['role']);

            if ($data['role'] === 'eleve' && ! empty($data['student_id'])) {
                Student::query()
                    ->whereNull('user_id')
                    ->findOrFail($data['student_id'])
                    ->update(['user_id' => $user->id]);
            }

            $this->audit->log('user.created', $user, [], ['role' => $data['role']], $actor);

            Password::sendResetLink(['email' => $user->email]);

            return $user;
        });
    }

    public function deactivate(User $target, User $actor): void
    {
        $target->update(['is_active' => false]);

        $this->audit->log('user.deactivated', $target, [], [], $actor);
    }

    public function reactivate(User $target, User $actor): void
    {
        $target->update(['is_active' => true]);

        $this->audit->log('user.reactivated', $target, [], [], $actor);
    }

    public function sendPasswordReset(User $target): void
    {
        Password::sendResetLink(['email' => $target->email]);
    }
}
```

Note on `Student::query()->whereNull('user_id')->findOrFail(...)`: `Student` uses `BelongsToTenant`, so this is already tenant-scoped as long as `TenantContext` is set — which it always is here, since `createAccount()` is only ever called from an authenticated `role:admin` controller action (Task 5), where `ResolveTenant` has already run. A `student_id` belonging to another tenant, or already linked to a different user, throws `ModelNotFoundException` → Laravel turns that into a 404, consistent with every other cross-tenant lookup in this app.

- [ ] **Step 4: Run the tests, confirm they pass**

```bash
php artisan test --compact --filter=UserManagementServiceTest
```

Expected: PASS (6/6).

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Domain/Users/Services/UserManagementService.php tests/Unit/Users/UserManagementServiceTest.php
git commit -m "feat(users): add UserManagementService (create/deactivate/reactivate/reset)"
```

---

## Task 5: `StoreUserRequest`, `UserManagementController`, routes

**Files:**
- Create: `app/Domain/Users/Http/Requests/StoreUserRequest.php`
- Create: `app/Domain/Users/Http/Controllers/UserManagementController.php`
- Modify: `routes/web.php`

**Interfaces:**
- Consumes: `UserManagementService` (Task 4), `UserPolicy` (Task 3, via `$this->authorize(...)`).
- Produces: routes `settings.users.index` (GET), `settings.users.store` (POST), `settings.users.reset-password` (POST), `settings.users.deactivate` (POST), `settings.users.reactivate` (POST).

- [ ] **Step 1: Create the FormRequest**

```php
<?php

namespace App\Domain\Users\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

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
            'role' => ['required', Rule::in(['admin', 'moniteur', 'eleve'])],
            'student_id' => ['nullable', 'integer'],
        ];
    }
}
```

- [ ] **Step 2: Create the controller**

```php
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
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly UserManagementService $users,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $roleFilter = $request->query('role');

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

        $this->users->sendPasswordReset($user);

        return back()->with('status', 'Lien de réinitialisation envoyé.');
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
```

`use Spatie\Permission\Models\Role;` is unused in the body above (the `User::role()` local scope from `HasRoles` is enough) — do not add that import; this note exists only so the implementer doesn't add it out of habit.

- [ ] **Step 3: Add the routes**

Edit `routes/web.php`, inside the existing `settings.` group (after the `document-types.` sub-group added by a previous feature):

```php
use App\Domain\Users\Http\Controllers\UserManagementController;
```

```php
        Route::prefix('users')
            ->name('users.')
            ->group(function () {
                Route::get('/', [UserManagementController::class, 'index'])->name('index');
                Route::post('/', [UserManagementController::class, 'store'])->name('store');
                Route::post('{user}/reset-password', [UserManagementController::class, 'sendPasswordReset'])->name('reset-password');
                Route::post('{user}/deactivate', [UserManagementController::class, 'deactivate'])->name('deactivate');
                Route::post('{user}/reactivate', [UserManagementController::class, 'reactivate'])->name('reactivate');
            });
```

- [ ] **Step 4: Pint + commit** (no new tests in this task — Task 8 covers the controller end-to-end; verify manually that routes register)

```bash
php artisan route:list --name=settings.users
vendor/bin/pint --dirty --format agent
git add app/Domain/Users/Http/Requests/StoreUserRequest.php app/Domain/Users/Http/Controllers/UserManagementController.php routes/web.php
git commit -m "feat(users): add UserManagementController and settings.users.* routes"
```

---

## Task 6: `users/index.blade.php` view + sidebar nav link

**Files:**
- Create: `resources/views/users/index.blade.php`
- Modify: `resources/views/layouts/partials/sidebar-nav.blade.php`

**Interfaces:**
- Consumes: the view-data array `index()` passes (Task 5): `$users` (paginator), `$roleFilter`, `$roleCounts`, `$unlinkedStudents`, `$preselectedStudent`.

- [ ] **Step 1: Create the view**

```blade
<x-app-layout>
    <x-slot name="header">Comptes utilisateurs</x-slot>

    <div class="py-6 space-y-4 max-w-5xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->any())
            <x-alert variant="danger">{{ $errors->first() }}</x-alert>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach (['admin' => 'Administrateurs', 'moniteur' => 'Moniteurs', 'eleve' => 'Élèves'] as $role => $label)
                <a
                    href="{{ route('settings.users.index', ['role' => $role]) }}"
                    @class([
                        'block rounded-ui-md p-4 shadow-soft-sm transition',
                        'bg-primary text-primary-content' => $roleFilter === $role,
                        'bg-surface hover:shadow-soft' => $roleFilter !== $role,
                    ])
                >
                    <p class="text-xs uppercase tracking-wide opacity-80">{{ $label }}</p>
                    <p class="text-2xl font-semibold">{{ $roleCounts[$role] ?? 0 }}</p>
                </a>
            @endforeach
        </div>

        @if ($roleFilter)
            <a href="{{ route('settings.users.index') }}" class="text-xs text-primary hover:underline">Voir tous les rôles</a>
        @endif

        <x-card>
            <h2 class="text-sm font-semibold text-content mb-3">Créer un compte</h2>
            <form method="POST" action="{{ route('settings.users.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @csrf
                <div>
                    <x-input-label for="name" value="Nom complet" />
                    <x-text-input id="name" name="name" class="block mt-1 w-full" :value="old('name', $preselectedStudent?->fullName())" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="email" value="E-mail" />
                    <x-text-input id="email" type="email" name="email" class="block mt-1 w-full" :value="old('email', $preselectedStudent?->email)" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="role" value="Rôle" />
                    <select id="role" name="role" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        <option value="admin" @selected(old('role') === 'admin')>Administrateur</option>
                        <option value="moniteur" @selected(old('role') === 'moniteur')>Moniteur</option>
                        <option value="eleve" @selected(old('role', $preselectedStudent ? 'eleve' : null) === 'eleve')>Élève</option>
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="student_id" value="Associer à un élève existant (optionnel)" />
                    <select id="student_id" name="student_id" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        <option value="">— Aucun —</option>
                        @foreach ($unlinkedStudents as $student)
                            <option value="{{ $student->id }}" @selected(old('student_id', $preselectedStudent?->id) == $student->id)>{{ $student->fullName() }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-content-muted mt-1">Ignoré si le rôle choisi n'est pas "Élève".</p>
                </div>
                <div class="sm:col-span-2">
                    <x-primary-button>Créer le compte</x-primary-button>
                </div>
            </form>
            <p class="text-xs text-content-muted mt-3">
                Le nouveau compte reçoit un e-mail avec un lien pour définir son mot de passe — aucun mot de passe n'est choisi ici.
                La création d'un compte moniteur ne crée pas son profil (permis, spécialités, disponibilités) : complétez-le ensuite depuis <a href="{{ route('instructors.index') }}" class="text-primary hover:underline">Moniteurs</a>.
            </p>
        </x-card>

        <x-card :padded="false">
            <ul class="divide-y divide-border/60">
                @forelse ($users as $user)
                    <li class="px-5 py-3 flex items-center justify-between gap-3 text-sm">
                        <div class="min-w-0">
                            <p class="text-content font-medium">{{ $user->name }}</p>
                            <p class="text-content-muted text-xs">{{ $user->email }}</p>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            @foreach ($user->roles as $role)
                                <x-badge variant="info">{{ $role->name }}</x-badge>
                            @endforeach
                            <x-badge :variant="$user->is_active ? 'success' : 'neutral'">
                                {{ $user->is_active ? 'Actif' : 'Désactivé' }}
                            </x-badge>
                            <form method="POST" action="{{ route('settings.users.reset-password', $user) }}">
                                @csrf
                                <button type="submit" class="text-xs text-primary hover:underline">Réinitialiser le mot de passe</button>
                            </form>
                            @if ($user->is_active)
                                <form method="POST" action="{{ route('settings.users.deactivate', $user) }}" onsubmit="return confirm('Désactiver ce compte ?');">
                                    @csrf
                                    <button type="submit" class="text-xs text-danger hover:underline">Désactiver</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('settings.users.reactivate', $user) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-success hover:underline">Réactiver</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-content-muted text-sm">Aucun compte pour le moment.</li>
                @endforelse
            </ul>
            <div class="px-5 py-3 border-t border-border/60">
                {{ $users->links() }}
            </div>
        </x-card>
    </div>
</x-app-layout>
```

- [ ] **Step 2: Add the sidebar nav link**

Edit `resources/views/layouts/partials/sidebar-nav.blade.php`, in the "Administration" block, right after `settings.document-types.*`:

```blade
                    <x-sidebar-link :href="route('settings.users.index')" :active="request()->routeIs('settings.users.*')" icon="users">Comptes utilisateurs</x-sidebar-link>
```

- [ ] **Step 3: Manual verification (no automated test in this task — Task 8 covers rendering)**

```bash
php artisan view:clear
```

Confirm no Blade compile errors by hitting the route once the full flow is testable (Task 8), or run any existing test that happens to render `users.index` early if one exists yet (none does before Task 8).

- [ ] **Step 4: Commit**

```bash
git add resources/views/users/index.blade.php resources/views/layouts/partials/sidebar-nav.blade.php
git commit -m "feat(users): add the account-management screen and its sidebar link"
```

---

## Task 7: "Créer un compte" action from a student's profile

**Files:**
- Modify: `resources/views/students/show.blade.php`

**Interfaces:**
- Consumes: `route('settings.users.index', ['student' => $student->id])` (Task 5's `index()` already handles the `?student=` query param).

- [ ] **Step 1: Read the current profile-header actions block**

Read `resources/views/students/show.blade.php` around its `@can('update', $student)` action links (near the top, alongside "Évaluer"/"Nouvelle facture"/"Modifier") to match the existing markup style exactly before editing.

- [ ] **Step 2: Add the conditional action**

In that same actions block, add (only when the student has no linked login account):

```blade
                    @can('update', $student)
                        @if (! $student->user_id)
                            <a href="{{ route('settings.users.index', ['student' => $student->id]) }}" class="text-sm text-content-secondary hover:text-primary transition">Créer un compte</a>
                        @endif
                    @endcan
```

Place it alongside the existing `@can('update', $student)` "Modifier" link, following the exact same `<a>` styling already used by the other action links in that block — do not introduce a new visual style for this one link.

- [ ] **Step 3: Write and run a small feature test**

```php
<?php

use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

it('shows a "Créer un compte" link on a student with no login account, but not on one that already has one', function () {
    $this->seed(RoleSeeder::class);
    $structure = Structure::factory()->create();
    $admin = User::factory()->create(['structure_id' => $structure->id]);
    $admin->assignRole('admin');

    $withoutAccount = Student::factory()->create(['structure_id' => $structure->id]);
    $withAccount = Student::factory()->create([
        'structure_id' => $structure->id,
        'user_id' => User::factory()->create(['structure_id' => $structure->id])->id,
    ]);

    $this->actingAs($admin)->get(route('students.show', $withoutAccount))
        ->assertSee(route('settings.users.index', ['student' => $withoutAccount->id]), false);

    $this->actingAs($admin)->get(route('students.show', $withAccount))
        ->assertDontSee('Créer un compte');
});
```

Add this to `tests/Feature/Students/StudentProfileTabsTest.php` (the existing file already covers `students.show` rendering) rather than a new file.

```bash
php artisan test --compact --filter=StudentProfileTabsTest
```

Expected: PASS.

- [ ] **Step 4: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add resources/views/students/show.blade.php tests/Feature/Students/StudentProfileTabsTest.php
git commit -m "feat(users): add a \"Créer un compte\" link from a student's profile"
```

---

## Task 8: End-to-end `UserManagementTest`, full suite, documentation

**Files:**
- Create: `tests/Feature/Users/UserManagementTest.php`
- Create: `docs/features/user-account-management.md`

**Interfaces:** none — this task only tests and documents what Tasks 1-7 already built.

- [ ] **Step 1: Write the comprehensive feature test**

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

it('lets an admin create an eleve account, an admin account, and a moniteur account from one screen', function () {
    Notification::fake();

    foreach (['eleve', 'admin', 'moniteur'] as $role) {
        $response = $this->actingAs($this->admin)->post(route('settings.users.store'), [
            'name' => "Test $role",
            'email' => "$role@example.com",
            'role' => $role,
        ]);

        $response->assertRedirect(route('settings.users.index'));

        $user = User::query()->where('email', "$role@example.com")->firstOrFail();
        expect($user->hasRole($role))->toBeTrue();
        expect($user->structure_id)->toBe($this->structure->id);

        Notification::assertSentTo($user, ResetPassword::class);
    }
});

it('links a new eleve account to an existing student with no login yet', function () {
    $student = Student::factory()->create(['structure_id' => $this->structure->id]);

    $this->actingAs($this->admin)->post(route('settings.users.store'), [
        'name' => 'Awa Test',
        'email' => 'awa@example.com',
        'role' => 'eleve',
        'student_id' => $student->id,
    ]);

    $user = User::query()->where('email', 'awa@example.com')->firstOrFail();
    expect($student->fresh()->user_id)->toBe($user->id);
});

it('shows the students-without-accounts list pre-filtered to the current tenant', function () {
    $otherStructure = Structure::factory()->create();
    $ownStudent = Student::factory()->create(['structure_id' => $this->structure->id, 'first_name' => 'Awa', 'last_name' => 'Tenant']);
    Student::factory()->create(['structure_id' => $otherStructure->id, 'first_name' => 'Autre', 'last_name' => 'Ecole']);

    $this->actingAs($this->admin)->get(route('settings.users.index'))
        ->assertSee('Awa Tenant')
        ->assertDontSee('Autre Ecole');
});

it('lets an admin trigger a password-reset link for an existing user', function () {
    Notification::fake();
    $target = User::factory()->create(['structure_id' => $this->structure->id]);

    $this->actingAs($this->admin)->post(route('settings.users.reset-password', $target))->assertRedirect();

    Notification::assertSentTo($target, ResetPassword::class);
});

it('lets an admin deactivate and reactivate a user', function () {
    $target = User::factory()->create(['structure_id' => $this->structure->id]);

    $this->actingAs($this->admin)->post(route('settings.users.deactivate', $target))->assertRedirect();
    expect($target->fresh()->is_active)->toBeFalse();

    $this->actingAs($this->admin)->post(route('settings.users.reactivate', $target))->assertRedirect();
    expect($target->fresh()->is_active)->toBeTrue();
});

it('refuses to let an admin deactivate their own account', function () {
    $this->actingAs($this->admin)->post(route('settings.users.deactivate', $this->admin))
        ->assertSessionHasErrors('user');

    expect($this->admin->fresh()->is_active)->toBeTrue();
});

// --- Tenant isolation ---------------------------------------------------

it('never lists another tenant\'s users', function () {
    $otherStructure = Structure::factory()->create();
    User::factory()->create(['structure_id' => $otherStructure->id, 'name' => 'Autre École Admin']);

    $this->actingAs($this->admin)->get(route('settings.users.index'))
        ->assertDontSee('Autre École Admin');
});

it('never lets an admin reset, deactivate, or reactivate another tenant\'s user', function () {
    $otherStructure = Structure::factory()->create();
    $target = User::factory()->create(['structure_id' => $otherStructure->id]);

    $this->actingAs($this->admin)->post(route('settings.users.reset-password', $target))->assertForbidden();
    $this->actingAs($this->admin)->post(route('settings.users.deactivate', $target))->assertForbidden();
    $this->actingAs($this->admin)->post(route('settings.users.reactivate', $target))->assertForbidden();
});

it('never lets an admin create an account linked to another tenant\'s unlinked student', function () {
    $otherStructure = Structure::factory()->create();
    $otherStudent = Student::factory()->create(['structure_id' => $otherStructure->id]);

    $this->actingAs($this->admin)->post(route('settings.users.store'), [
        'name' => 'Test',
        'email' => 'cross-tenant@example.com',
        'role' => 'eleve',
        'student_id' => $otherStudent->id,
    ])->assertNotFound();

    expect($otherStudent->fresh()->user_id)->toBeNull();
});

// --- Policy denial --------------------------------------------------------

it('denies a moniteur from accessing the account-management screen entirely', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->get(route('settings.users.index'))->assertForbidden();
    $this->actingAs($moniteur)->post(route('settings.users.store'), [
        'name' => 'Test', 'email' => 'x@example.com', 'role' => 'eleve',
    ])->assertForbidden();
});
```

- [ ] **Step 2: Run it**

```bash
php artisan test --compact --filter=UserManagement
```

Expected: PASS, all tests green (this filter matches both `UserManagementTest` and `UserManagementServiceTest` from Task 4 — that's fine, both should already be green).

- [ ] **Step 3: Run the full suite**

```bash
php artisan test --compact
```

Expected: 100% green, no regressions in the pre-existing suite (this is the integration checkpoint — if something from Tasks 1-7 doesn't actually fit together, it surfaces here).

- [ ] **Step 4: Write the documentation**

```markdown
# Account Management (Users domain)

Admins manage every account in their tenant — admin, moniteur, and eleve —
from one screen: **Paramètres > Comptes utilisateurs** (`/settings/users`).

## What this does and doesn't do

- Creates a `User` row with a role assigned via `spatie/laravel-permission`.
  This is the *only* place in the app staff can create a `moniteur` account —
  `/instructors` still owns the professional profile (license number,
  specialties, availabilities) layered on top of one; it has never created
  the underlying login account itself (see `InstructorController::store()`,
  which requires an existing `user_id`).
- For an `eleve` account, optionally links it to an existing `Student` row
  that has no `user_id` yet — closing the gap where a student created via
  the admin `/students` screen (`EnrollmentService::register()`) never got a
  login account, unlike the public self-registration flow
  (`PublicStudentRegistrationService`), which is the only other place a
  `User`+`Student` pair is created together.
- Triggers Laravel's standard password-reset email (`Password::sendResetLink()`)
  both at account creation and on demand — no temporary password is ever
  chosen by an admin or shown on screen. `email_verified_at` is set
  immediately at creation (see `UserManagementService::createAccount()`'s
  docblock for why this doesn't need the self-registration OTP flow).
- Deactivates/reactivates an account (`users.is_active`) rather than
  deleting it — a deactivated user can't log in (`LoginRequest`) and an
  already-open session is killed on its very next request
  (`EnsureUserIsActive` middleware, appended globally to the `web` group).

## Tenant isolation

Every query goes through `User`'s `BelongsToTenant` global scope; `UserPolicy`
re-checks `structure_id` explicitly on top of that for `update` (reset/
deactivate/reactivate), matching the pattern used by every other tenant-scoped
policy in this app. `UserManagementService::createAccount()`'s student-linking
lookup is scoped the same way — a `student_id` belonging to another tenant
404s via `ModelNotFoundException`, never silently ignored or cross-linked.

## Residual risk: password-reset tokens aren't tenant-scoped

`password_reset_tokens` is keyed by plain `email` (Laravel's default schema),
and `users` is only unique per `(structure_id, email)` — two different
tenants can share an admin's email address. If they do, which tenant's
account actually gets its password changed when the reset link is clicked is
ambiguous, since the link is a public route with no `TenantContext` active.
This is a pre-existing gap in the app's Breeze password-reset flow (already
true today for any two tenants sharing an email), not something this feature
introduces — fixing it would mean redesigning Laravel's password broker to be
tenant-aware, which is out of scope here. In practice this only matters if
the same person (or a coincidence) shares one email across two different
driving schools' accounts on this platform.
```

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add tests/Feature/Users/UserManagementTest.php docs/features/user-account-management.md
git commit -m "test(users): end-to-end coverage for account management, document the feature"
```

---

## Final checklist

- [ ] `php artisan test --compact` — full suite green.
- [ ] `vendor/bin/pint --format agent` (whole tree, no `--dirty`) — clean.
- [ ] Manually walk the flow in a real browser: create an eleve account linked to an existing student, confirm the reset-password email is queued/logged, deactivate that account and confirm login is refused, reactivate it, confirm `/instructors` still works unchanged for attaching a profile to a freshly-created moniteur account.
