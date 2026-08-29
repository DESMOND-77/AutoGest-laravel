# Inscription élève OTP + Dossier - Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the current one-shot public student registration form with a multi-step flow - self-service account creation with password login, OTP email verification, a per-tenant-configurable required-document dossier that students submit themselves, and per-document (not per-dossier) admin review with automatic lifecycle transitions.

**Architecture:** Two sequential phases sharing one reordered `LifecycleStage` graph.
*Phase A* (Tasks 1–8) replaces `PublicStudentRegistrationService`'s Student-only creation with a User+Student creation, adds a new `email_otps` table/service/Mailable, and chains two automatic `LifecycleService::transitionTo()` calls (`Prospect → PreEnrollment → DossierSetup`) off a new `StudentEmailVerified` event once OTP is verified. This phase alone is fully testable end-to-end (account creation → OTP → student lands at `DossierSetup`) without touching documents at all.
*Phase B* (Tasks 9–14) adds a new `RequiredDocumentType` model (Students domain, admin-configurable per tenant), extends the existing `Document` model with review fields, and adds the student-facing "Constitution du dossier" screen plus the admin review queue that drives the remaining two automatic transitions (`Validation → DossierSetup` on rejection, `Validation → Enrollment` once every active required type is approved).

Everything reuses existing infrastructure: `BelongsToTenant`/`TenantContext` for isolation (no new tenancy mechanism), `LifecycleService::transitionTo()` for every stage change (never a direct `lifecycle_stage` write), `AuditService` for logging, the existing `DocumentService` versioning pattern (extended, not replaced), and the `StudentRegistrationLink` token-resolves-tenant mechanism (unchanged).

**Tech Stack:** Laravel 12, PHP 8.5, Pest 3, Blade + Alpine.js + Tailwind (Soft UI tokens already in the app), Laravel Mail (first `Mailable` in this codebase - mail config already present in `.env.example`).

**Spec:** `docs/superpowers/specs/2026-08-23-inscription-eleve-otp-dossier-design.md`

## Global Constraints

- Every `lifecycle_stage` change - automatic or manual - goes through `LifecycleService::transitionTo()`. Never assign `lifecycle_stage` directly, not even in a listener.
- `structure_id` is never read from request input for anything created during public registration or the eleve self-service flow - it is always resolved via `TenantContext` (set once, from the token, in `PublicStudentRegistrationService::register()`), exactly like the existing `Student` creation already does. `User` also uses `BelongsToTenant`, so the same auto-stamp applies to the new `User::create()` call.
- No parallel tenancy/authorization mechanism: reuse `BelongsToTenant`, `TenantContext`, the existing `role:` middleware, and Policies. A new ability goes on an existing Policy (`DocumentPolicy`) when the model it targets already has one; a brand-new model gets its own Policy mirroring `StudentRegistrationLinkPolicy`'s `hasRole('admin') && $model->structure_id === $user->structure_id` shape.
- Domain-local listeners under `app/Domain/*/Listeners` are **not** auto-discovered in this codebase - `LogStageChange` proves this: it's registered by hand via `Event::listen(...)` in `AppServiceProvider::boot()`. Every new listener in this plan must be registered there the same way.
- New Policies must be registered via `Gate::policy(...)` in `AppServiceProvider::boot()`.
- OTP codes are stored hashed (`sha256`), never in plain text, never logged. Mirrors the existing `StudentRegistrationLink::token_hash` pattern.
- `DuplicateRegistration`'s message stays generic - it must never reveal *which* field (email vs. phone vs. account-already-exists) matched, and never which tenant an existing account belongs to.
- Never flash a `password`/`password_confirmation` field back into the session (`$request->flashExcept([...])`, never `$request->flash()`, once the registration form collects a password).
- Every new route/model gets an explicit tenant-isolation test (this project's most recurring bug class, per `docs/audit/multi-tenancy-audit.md`).
- Run `vendor/bin/pint --dirty --format agent` after any PHP file change, before considering a task done.
- `php artisan test --compact --filter=<Name>` after each task; the full suite (`php artisan test --compact`) at the end of Task 8 and again at the end of Task 14.

---

## File Structure

**Phase A - new files**
- `database/migrations/xxxx_create_email_otps_table.php`
- `app/Domain/Students/Models/EmailOtp.php`
- `app/Domain/Students/Database/Factories/EmailOtpFactory.php`
- `app/Domain/Students/Exceptions/InvalidOtp.php`
- `app/Domain/Students/Services/EmailOtpService.php`
- `app/Domain/Students/Mail/EmailOtpMail.php`
- `resources/views/emails/otp.blade.php`
- `app/Domain/Students/Events/StudentEmailVerified.php`
- `app/Domain/Students/Listeners/ActivateStudentAfterEmailVerification.php`
- `app/Domain/Students/Http/Middleware/EnsureEmailOtpVerified.php`
- `app/Domain/Students/Http/Controllers/EmailOtpController.php`
- `app/Domain/Students/Http/Requests/VerifyEmailOtpRequest.php`
- `resources/views/eleve/verification-otp.blade.php`
- `tests/Unit/Students/EmailOtpServiceTest.php`
- `tests/Feature/Students/EmailOtpVerificationTest.php`

**Phase A - modified files**
- `app/Domain/Students/Enums/LifecycleStage.php` (reordered transition graph)
- `tests/Unit/Students/LifecycleServiceTest.php` (assertions for the new graph)
- `config/services.php`, `.env.example` (new `email_otp` config block)
- `app/Domain/Students/Http/Requests/PublicStudentRegistrationRequest.php` (password + required email/birth_date fields)
- `app/Domain/Students/Http/Controllers/PublicStudentRegistrationController.php` (redirect to OTP screen, no more `success()`)
- `app/Domain/Students/Services/PublicStudentRegistrationService.php` (creates `User` + `Student`, sends OTP, auto-logs-in)
- `resources/views/register/student.blade.php` (password fields)
- `routes/web.php` (OTP routes, `otp.verified` middleware on the eleve/quiz groups, drop `public-registration.success`)
- `bootstrap/app.php` (alias `otp.verified`)
- `app/Providers/AppServiceProvider.php` (register `StudentEmailVerified` listener)
- `tests/Feature/Students/PublicStudentRegistrationTest.php` (adapted to the new flow)
- `docs/features/student-public-registration.md` (documents the new flow)

**Removed**
- `resources/views/register/student-success.blade.php` (superseded by the OTP screen)

**Phase B - new files**
- `database/migrations/xxxx_create_required_document_types_table.php`
- `database/migrations/xxxx_add_review_fields_to_documents_table.php`
- `app/Domain/Students/Models/RequiredDocumentType.php`
- `app/Domain/Students/Database/Factories/RequiredDocumentTypeFactory.php`
- `app/Domain/Students/Policies/RequiredDocumentTypePolicy.php`
- `app/Domain/Students/Http/Requests/StoreRequiredDocumentTypeRequest.php`
- `app/Domain/Students/Http/Requests/UpdateRequiredDocumentTypeRequest.php`
- `app/Domain/Students/Http/Controllers/RequiredDocumentTypeController.php`
- `resources/views/settings/document-types.blade.php`
- `app/Domain/Documents/Enums/DocumentReviewStatus.php`
- `app/Domain/Students/Http/Requests/UploadDossierDocumentRequest.php`
- `app/Domain/Students/Http/Controllers/StudentDossierController.php`
- `resources/views/eleve/dossier.blade.php`
- `app/Domain/Documents/Http/Requests/RejectDossierDocumentRequest.php`
- `app/Domain/Documents/Http/Controllers/DocumentReviewController.php`
- `resources/views/students/dossier-review.blade.php`
- `tests/Feature/Students/RequiredDocumentTypeAdminTest.php`
- `tests/Feature/Students/StudentDossierTest.php`
- `tests/Feature/Documents/DocumentReviewTest.php`
- `tests/Feature/Students/DossierEndToEndTest.php`

**Phase B - modified files**
- `app/Domain/Documents/Models/Document.php` (new fillable/casts/relations)
- `app/Domain/Documents/Services/DocumentService.php` (`$requiredDocumentType` param, review-status reset on new version)
- `app/Domain/Documents/Policies/DocumentPolicy.php` (new `review` ability)
- `app/Providers/AppServiceProvider.php` (register `RequiredDocumentTypePolicy`)
- `routes/web.php` (document-types admin CRUD, eleve dossier routes, admin review routes)
- `resources/views/layouts/partials/sidebar-nav.blade.php` (nav links: "Pièces requises", "Dossiers en attente")
- `docs/features/student-public-registration.md` (dossier section)

---

## Task 1: `email_otps` table, `EmailOtp` model, factory

**Files:**
- Create: `database/migrations/xxxx_create_email_otps_table.php`
- Create: `app/Domain/Students/Models/EmailOtp.php`
- Create: `app/Domain/Students/Database/Factories/EmailOtpFactory.php`
- Test: `tests/Unit/Students/EmailOtpServiceTest.php` (this task only adds the model-level tests; the service tests land in Task 3)

**Interfaces:**
- Produces: `EmailOtp` model with `user_id, code_hash, expires_at, attempts, consumed_at` - `user_id` unique (one active OTP row per user, upserted).

- [ ] **Step 1: Create the migration**

```bash
php artisan make:migration create_email_otps_table --no-interaction
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
        Schema::create('email_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('code_hash', 64);
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_otps');
    }
};
```

`user_id` is `unique()` on purpose - the spec requires "un User n'a jamais plus d'un OTP actif à la fois"; a unique column lets `EmailOtpService::generate()` use a plain `updateOrCreate()` instead of a manual delete-then-insert.

- [ ] **Step 2: Create the model**

```php
<?php

namespace App\Domain\Students\Models;

use App\Domain\Students\Database\Factories\EmailOtpFactory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailOtp extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return EmailOtpFactory::new();
    }

    protected $fillable = [
        'user_id',
        'code_hash',
        'expires_at',
        'attempts',
        'consumed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

Not tenant-scoped: this table is keyed by `user_id`, not `structure_id`, and is never queried across tenants - it doesn't need `BelongsToTenant`.

- [ ] **Step 3: Create the factory**

```php
<?php

namespace App\Domain\Students\Database\Factories;

use App\Domain\Students\Models\EmailOtp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailOtp>
 */
class EmailOtpFactory extends Factory
{
    protected $model = EmailOtp::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'code_hash' => hash('sha256', '123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'consumed_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subMinute()]);
    }

    public function exhausted(): static
    {
        return $this->state(fn () => ['attempts' => 5]);
    }

    public function consumed(): static
    {
        return $this->state(fn () => ['consumed_at' => now()]);
    }
}
```

- [ ] **Step 4: Run migrations and Pint**

```bash
php artisan migrate --no-interaction
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 5: Commit**

```bash
git add database/migrations app/Domain/Students/Models/EmailOtp.php app/Domain/Students/Database/Factories/EmailOtpFactory.php
git commit -m "feat(students): add email_otps table and EmailOtp model"
```

---

## Task 2: Reorder `LifecycleStage`'s transition graph

**Files:**
- Modify: `app/Domain/Students/Enums/LifecycleStage.php`
- Modify: `tests/Unit/Students/LifecycleServiceTest.php`
- Test: run the full Students unit+feature suite to catch any other test asserting the old graph

**Interfaces:**
- Produces: the same 15 `LifecycleStage` cases (no renaming, no removal - string values on rows already in the database stay valid), with `allowedNextStages()` matching the design's §18-40 ordering. `PracticalExam`'s existing back-edge to `ContinuousEvaluation` is unchanged; a new back-edge `Validation → DossierSetup` is added.

- [ ] **Step 1: Update `allowedNextStages()`**

Edit `app/Domain/Students/Enums/LifecycleStage.php`:

```php
    /**
     * @return LifecycleStage[] stages this one may transition to.
     */
    public function allowedNextStages(): array
    {
        return match ($this) {
            self::Prospect => [self::PreEnrollment],
            self::PreEnrollment => [self::DossierSetup],
            self::DossierSetup => [self::Validation],
            self::Validation => [self::Enrollment, self::DossierSetup],
            self::Enrollment => [self::Payment],
            self::Payment => [self::TheoryCourse],
            self::TheoryCourse => [self::MockExams],
            self::MockExams => [self::CodeObtained],
            self::CodeObtained => [self::PracticalCourse],
            self::PracticalCourse => [self::ContinuousEvaluation],
            self::ContinuousEvaluation => [self::ReadyForExam],
            self::ReadyForExam => [self::PracticalExam],
            self::PracticalExam => [self::LicenseObtained, self::ContinuousEvaluation],
            self::LicenseObtained => [self::FormerStudent],
            self::FormerStudent => [],
        };
    }
```

Also update the enum's class docblock, which currently describes the old ordering:

```php
/**
 * The student lifecycle from the 2026-08-23 design (docs/superpowers/specs/
 * 2026-08-23-inscription-eleve-otp-dossier-design.md): Prospect -> ... ->
 * LicenseObtained -> FormerStudent, with two allowed back-edges - Validation
 * -> DossierSetup on a rejected document, and PracticalExam ->
 * ContinuousEvaluation on a failed exam.
 */
```

- [ ] **Step 2: Update the existing unit test's golden-path assertion**

`tests/Unit/Students/LifecycleServiceTest.php`'s three existing tests (`Prospect → PreEnrollment`, "rejects a transition that skips stages" targeting `LicenseObtained`, and the `PracticalExam → ContinuousEvaluation` retake loop) all still hold true under the new graph unchanged - `Prospect`'s only allowed target is still `PreEnrollment`, and `LicenseObtained` is still unreachable directly from `Prospect`. No edit needed to those three, but add one new test documenting the new back-edge:

```php
it('allows a rejected dossier to send the student back to dossier setup', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->stage(LifecycleStage::Validation)->create(['structure_id' => $structure->id]);

    (new LifecycleService)->transitionTo($student, LifecycleStage::DossierSetup);

    expect($student->fresh()->lifecycle_stage)->toBe(LifecycleStage::DossierSetup);
});

it('rejects skipping straight from dossier setup to enrollment without going through validation', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->stage(LifecycleStage::DossierSetup)->create(['structure_id' => $structure->id]);

    expect(fn () => (new LifecycleService)->transitionTo($student, LifecycleStage::Enrollment))
        ->toThrow(InvalidStageTransition::class);
});
```

- [ ] **Step 3: Run the tests, then search for any other place asserting the old graph**

```bash
php artisan test --compact --filter=LifecycleServiceTest
grep -rn "LifecycleStage::Enrollment\|LifecycleStage::PreEnrollment\|LifecycleStage::DossierSetup\|LifecycleStage::Validation" app tests resources
```

Read every match. Fix any test or seeder that assumed `PreEnrollment → Enrollment` was a direct transition (there should be none outside `LifecycleServiceTest`, since no route uses `advanceStage` to jump past `DossierSetup`/`Validation` yet - `StudentController::advanceStage` just calls `LifecycleService::transitionTo()` with whatever the caller passed, so it's graph-agnostic).

Expected: PASS, no other file needs changes.

- [ ] **Step 4: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Domain/Students/Enums/LifecycleStage.php tests/Unit/Students/LifecycleServiceTest.php
git commit -m "feat(students): reorder lifecycle graph for the OTP/dossier flow"
```

---

## Task 3: `EmailOtpService` + `InvalidOtp` exception + config

**Files:**
- Create: `app/Domain/Students/Exceptions/InvalidOtp.php`
- Create: `app/Domain/Students/Services/EmailOtpService.php`
- Modify: `config/services.php`
- Modify: `.env.example`
- Modify: `tests/Unit/Students/EmailOtpServiceTest.php` (created empty in Task 1, filled in here)

**Interfaces:**
- Consumes: `EmailOtp` model (Task 1), `App\Models\User`.
- Produces: `EmailOtpService::generate(User $user): string` (plain 6-digit code, also sends the Mailable - wired up fully once Task 4 lands; for this task, generate() calls `Mail::to()->send()` against a Mailable that will exist by the time Task 4 finishes - write Task 3 and Task 4 in order, or stub the Mailable class name now since PHP doesn't check the class exists until it's actually instantiated at runtime). `EmailOtpService::resend(User $user): string` (delegates to `generate()`). `EmailOtpService::verify(User $user, string $code): void` (throws `InvalidOtp`).

Because `generate()` needs `EmailOtpMail` (Task 4) to compile-reference it, do Task 4 immediately after this task's Step 1-2, before writing the service body in Step 3. The steps below are ordered so the class exists before it's referenced.

- [ ] **Step 1: Add the config block**

Edit `config/services.php`, after the existing `'student_registration'` block:

```php
    'email_otp' => [
        // How long a freshly generated OTP code stays valid, and how many
        // wrong guesses are allowed before it must be reissued. See
        // docs/features/student-public-registration.md.
        'expiry_minutes' => env('EMAIL_OTP_EXPIRY_MINUTES', 10),
        'max_attempts' => env('EMAIL_OTP_MAX_ATTEMPTS', 5),
    ],
```

Edit `.env.example`, after the existing `STUDENT_REGISTRATION_LINK_TTL_DAYS` line:

```
# How long an email-verification OTP code stays valid, and how many wrong
# attempts are allowed before it must be reissued (settings > Inscription
# publique / eleve verification-otp screen).
EMAIL_OTP_EXPIRY_MINUTES=10
EMAIL_OTP_MAX_ATTEMPTS=5
```

- [ ] **Step 2: Create the exception**

```php
<?php

namespace App\Domain\Students\Exceptions;

use RuntimeException;

/**
 * Mirrors InvalidRegistrationLink's shape (reason() + static constructors),
 * but here every reason is safe to show - unlike the public registration
 * token, the OTP screen already knows exactly who the visitor is
 * (authenticated as the eleve, before verification), so there's nothing to
 * enumerate by distinguishing "wrong code" from "expired" from "too many
 * attempts".
 */
class InvalidOtp extends RuntimeException
{
    private function __construct(
        string $message,
        public readonly string $reason,
    ) {
        parent::__construct($message);
    }

    public static function invalid(): self
    {
        return new self('Code invalide.', 'invalid');
    }

    public static function expired(): self
    {
        return new self('Ce code a expiré. Demandez-en un nouveau.', 'expired');
    }

    public static function exhausted(): self
    {
        return new self('Nombre maximal de tentatives atteint. Demandez un nouveau code.', 'exhausted');
    }
}
```

- [ ] **Step 3: Write the failing unit test**

```php
<?php

use App\Domain\Students\Exceptions\InvalidOtp;
use App\Domain\Students\Models\EmailOtp;
use App\Domain\Students\Services\EmailOtpService;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->service = app(EmailOtpService::class);
    $this->user = User::factory()->create();
});

it('generates a 6-digit code, stores only its hash, and emails it', function () {
    $code = $this->service->generate($this->user);

    expect($code)->toMatch('/^\d{6}$/');

    $otp = EmailOtp::query()->where('user_id', $this->user->id)->firstOrFail();
    expect($otp->code_hash)->toBe(hash('sha256', $code));
    expect($otp->consumed_at)->toBeNull();
    expect($otp->attempts)->toBe(0);

    Mail::assertSent(\App\Domain\Students\Mail\EmailOtpMail::class);
});

it('replaces any previous code on resend instead of stacking a second row', function () {
    $first = $this->service->generate($this->user);
    $second = $this->service->resend($this->user);

    expect($second)->not->toBe($first);
    expect(EmailOtp::query()->where('user_id', $this->user->id)->count())->toBe(1);

    // The old code no longer verifies.
    expect(fn () => $this->service->verify($this->user, $first))->toThrow(InvalidOtp::class);
});

it('verifies a correct code and consumes it', function () {
    $code = $this->service->generate($this->user);

    $this->service->verify($this->user, $code);

    expect(EmailOtp::query()->where('user_id', $this->user->id)->first()->consumed_at)->not->toBeNull();
});

it('rejects a wrong code and increments the attempt counter', function () {
    $this->service->generate($this->user);

    expect(fn () => $this->service->verify($this->user, '000000'))->toThrow(InvalidOtp::class);
    expect(EmailOtp::query()->where('user_id', $this->user->id)->first()->attempts)->toBe(1);
});

it('rejects an already-consumed code', function () {
    $code = $this->service->generate($this->user);
    $this->service->verify($this->user, $code);

    expect(fn () => $this->service->verify($this->user, $code))->toThrow(InvalidOtp::class);
});

it('rejects an expired code with a distinct reason', function () {
    EmailOtp::factory()->expired()->create(['user_id' => $this->user->id]);

    expect(fn () => $this->service->verify($this->user, '123456'))
        ->toThrow(fn (InvalidOtp $e) => $e->reason === 'expired');
});

it('rejects after the maximum number of attempts even with the right code', function () {
    EmailOtp::factory()->exhausted()->create([
        'user_id' => $this->user->id,
        'code_hash' => hash('sha256', '654321'),
    ]);

    expect(fn () => $this->service->verify($this->user, '654321'))
        ->toThrow(fn (InvalidOtp $e) => $e->reason === 'exhausted');
});

it('rejects when no code was ever generated', function () {
    expect(fn () => $this->service->verify($this->user, '123456'))->toThrow(InvalidOtp::class);
});
```

- [ ] **Step 4: Run it, confirm it fails**

```bash
php artisan test --compact --filter=EmailOtpServiceTest
```

Expected: FAIL - `App\Domain\Students\Services\EmailOtpService` and `App\Domain\Students\Mail\EmailOtpMail` don't exist yet.

- [ ] **Step 5: Create `EmailOtpMail` now (fully specified in Task 4 - create it here as a minimal stub so this task's service compiles, then Task 4 fills in the real subject/view)**

```php
<?php

namespace App\Domain\Students\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $code,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre code de vérification');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.otp', with: ['code' => $this->code]);
    }
}
```

(This is the same class Task 4 finalizes - Task 4 just adds the Blade view it references. Nothing here is thrown away.)

- [ ] **Step 6: Implement `EmailOtpService`**

```php
<?php

namespace App\Domain\Students\Services;

use App\Domain\Students\Exceptions\InvalidOtp;
use App\Domain\Students\Mail\EmailOtpMail;
use App\Domain\Students\Models\EmailOtp;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * The only place allowed to generate, resend, or verify an email OTP. A user
 * only ever has one active row (see the migration's unique(user_id)) -
 * generate() upserts, so a resend transparently replaces and invalidates
 * whatever code came before it.
 */
class EmailOtpService
{
    public function generate(User $user): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailOtp::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'code_hash' => hash('sha256', $code),
                'expires_at' => now()->addMinutes((int) config('services.email_otp.expiry_minutes')),
                'attempts' => 0,
                'consumed_at' => null,
            ],
        );

        Mail::to($user->email)->send(new EmailOtpMail($code));

        return $code;
    }

    public function resend(User $user): string
    {
        return $this->generate($user);
    }

    /**
     * @throws InvalidOtp
     */
    public function verify(User $user, string $code): void
    {
        $otp = EmailOtp::query()->where('user_id', $user->id)->first();

        if (! $otp || $otp->consumed_at !== null) {
            throw InvalidOtp::invalid();
        }

        if ($otp->expires_at->isPast()) {
            throw InvalidOtp::expired();
        }

        if ($otp->attempts >= (int) config('services.email_otp.max_attempts')) {
            throw InvalidOtp::exhausted();
        }

        if (! hash_equals($otp->code_hash, hash('sha256', $code))) {
            $otp->increment('attempts');

            throw InvalidOtp::invalid();
        }

        $otp->update(['consumed_at' => now()]);
    }
}
```

- [ ] **Step 7: Run the test again, confirm it passes**

```bash
php artisan test --compact --filter=EmailOtpServiceTest
```

Expected: PASS (8 tests).

- [ ] **Step 8: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Domain/Students/Exceptions/InvalidOtp.php app/Domain/Students/Services/EmailOtpService.php app/Domain/Students/Mail/EmailOtpMail.php config/services.php .env.example tests/Unit/Students/EmailOtpServiceTest.php
git commit -m "feat(students): add EmailOtpService (generate/resend/verify)"
```

---

## Task 4: `EmailOtpMail` view

**Files:**
- Modify: `app/Domain/Students/Mail/EmailOtpMail.php` (already created in Task 3 - no change needed, this task only adds its view)
- Create: `resources/views/emails/otp.blade.php`

**Interfaces:**
- Consumes: `EmailOtpMail`'s `$code` variable, passed via `Content(with: ['code' => $this->code])`.

- [ ] **Step 1: Create the plain Blade mail view**

```blade
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: sans-serif; color: #1f2937;">
    <p>Bonjour,</p>
    <p>Voici votre code de vérification :</p>
    <p style="font-size: 28px; font-weight: 700; letter-spacing: 0.3em;">{{ $code }}</p>
    <p>Ce code expire dans {{ config('services.email_otp.expiry_minutes') }} minutes.</p>
    <p>Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail.</p>
</body>
</html>
```

- [ ] **Step 2: Manually verify rendering**

```bash
php artisan tinker --execute 'echo (new App\Domain\Students\Mail\EmailOtpMail("123456"))->render();'
```

Expected: renders HTML containing `123456`, no errors. (This is a render check, not a stored side effect - acceptable per this project's "no tinker for things tests already cover" rule, since no test exercises the *rendered HTML output* itself, only that the Mailable is sent.)

- [ ] **Step 3: Commit**

```bash
git add resources/views/emails/otp.blade.php
git commit -m "feat(students): add the OTP email view"
```

---

## Task 5: `StudentEmailVerified` event + auto-transition listener

**Files:**
- Create: `app/Domain/Students/Events/StudentEmailVerified.php`
- Create: `app/Domain/Students/Listeners/ActivateStudentAfterEmailVerification.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Unit/Students/ActivateStudentAfterEmailVerificationTest.php`

**Interfaces:**
- Consumes: `LifecycleService::transitionTo()` (existing), `LifecycleStage::PreEnrollment`/`DossierSetup`.
- Produces: `StudentEmailVerified` (holds `public readonly Student $student`), dispatched by `EmailOtpController::verify()` in Task 7.

- [ ] **Step 1: Create the event**

```php
<?php

namespace App\Domain\Students\Events;

use App\Domain\Students\Models\Student;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentEmailVerified
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Student $student,
    ) {}
}
```

- [ ] **Step 2: Write the failing test**

```php
<?php

use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Events\StudentEmailVerified;
use App\Domain\Students\Listeners\ActivateStudentAfterEmailVerification;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;

it('chains prospect straight through pre-enrollment to dossier setup', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);

    expect($student->lifecycle_stage)->toBe(LifecycleStage::Prospect);

    (new ActivateStudentAfterEmailVerification)->handle(new StudentEmailVerified($student));

    expect($student->fresh()->lifecycle_stage)->toBe(LifecycleStage::DossierSetup);
});
```

- [ ] **Step 3: Run it, confirm it fails**

```bash
php artisan test --compact --filter=ActivateStudentAfterEmailVerificationTest
```

Expected: FAIL - class not found.

- [ ] **Step 4: Implement the listener**

```php
<?php

namespace App\Domain\Students\Listeners;

use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Events\StudentEmailVerified;
use App\Domain\Students\Services\LifecycleService;

/**
 * Both transitions fire back-to-back with no visible intermediate state for
 * the student - see the design's "Transitions automatiques vs manuelles"
 * table. Injecting LifecycleService (rather than `new`-ing it, unlike
 * LogStageChange's plain `new LifecycleService` in its own tests) keeps this
 * listener consistent with how every controller in this codebase resolves
 * it, and lets Laravel's container inject it normally when the event fires
 * for real.
 */
class ActivateStudentAfterEmailVerification
{
    public function __construct(
        private readonly LifecycleService $lifecycle,
    ) {}

    public function handle(StudentEmailVerified $event): void
    {
        $this->lifecycle->transitionTo($event->student, LifecycleStage::PreEnrollment);
        $this->lifecycle->transitionTo($event->student, LifecycleStage::DossierSetup);
    }
}
```

Update the test's manual instantiation to go through the container so both styles are covered:

```php
    (new ActivateStudentAfterEmailVerification(new \App\Domain\Students\Services\LifecycleService))
        ->handle(new StudentEmailVerified($student));
```

- [ ] **Step 5: Register the listener in `AppServiceProvider::boot()`**

```php
use App\Domain\Students\Events\StudentEmailVerified;
use App\Domain\Students\Listeners\ActivateStudentAfterEmailVerification;
```

```php
        Event::listen(StudentStageChanged::class, LogStageChange::class);
        Event::listen(StudentEmailVerified::class, ActivateStudentAfterEmailVerification::class);
```

- [ ] **Step 6: Run the test, confirm it passes**

```bash
php artisan test --compact --filter=ActivateStudentAfterEmailVerificationTest
```

Expected: PASS.

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Domain/Students/Events/StudentEmailVerified.php app/Domain/Students/Listeners/ActivateStudentAfterEmailVerification.php app/Providers/AppServiceProvider.php tests/Unit/Students/ActivateStudentAfterEmailVerificationTest.php
git commit -m "feat(students): auto-transition Prospect to DossierSetup on email verification"
```

---

## Task 6: Rewrite public registration - create a `User` + `Student`, send OTP, auto-login

**Files:**
- Modify: `app/Domain/Students/Http/Requests/PublicStudentRegistrationRequest.php`
- Modify: `app/Domain/Students/Services/PublicStudentRegistrationService.php`
- Modify: `app/Domain/Students/Http/Controllers/PublicStudentRegistrationController.php`
- Modify: `resources/views/register/student.blade.php`
- Delete: `resources/views/register/student-success.blade.php`
- Modify: `routes/web.php` (drop `public-registration.success`)

**Interfaces:**
- Consumes: `EmailOtpService::generate()` (Task 3), `App\Models\User`.
- Produces: `PublicStudentRegistrationRequest::accountData(): array{name, email, password}`, `PublicStudentRegistrationRequest::studentData(): array` (unchanged shape, minus `registration_token`/`password`/`password_confirmation`). `PublicStudentRegistrationService::register(string $plainToken, array $accountData, array $studentData): Student` (signature changes from the old single-`$data` form - this is a breaking change to the method Task 8's tests must follow).

- [ ] **Step 1: Update the FormRequest**

```php
<?php

namespace App\Domain\Students\Http\Requests;

use App\Domain\Students\Enums\CourseType;
use App\Domain\Students\Enums\LicenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

/**
 * No `tenant_id` / `structure_id` field exists here, on purpose - see §29-30
 * of docs/superpowers/specs/2026-08-23-inscription-eleve-otp-dossier-design.md.
 * The only thing this request accepts that identifies a tenant at all is
 * `registration_token`, and even that isn't trusted directly: the controller
 * hands it to StudentRegistrationLinkService::validate(), which re-derives
 * the tenant server-side from the hashed, stored token.
 */
class PublicStudentRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Open to the public by design - the registration_token is the
        // authorization mechanism, not a Policy/Gate check (there is no
        // authenticated actor to check one against).
        return true;
    }

    public function rules(): array
    {
        return [
            'registration_token' => ['required', 'string'],
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['required', 'string', 'max:30'],
            'birth_date' => ['required', 'date', 'before:today'],
            'birth_place' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'license_category' => ['required', new Enum(LicenseCategory::class)],
            'course_type' => ['required', new Enum(CourseType::class)],
        ];
    }

    /**
     * Everything the Student row needs - structure_id is never in this
     * array (auto-stamped from TenantContext, see PublicStudentRegistrationService).
     */
    public function studentData(): array
    {
        return $this->safe()->except(['registration_token', 'password', 'password_confirmation']);
    }

    /**
     * Everything the login account needs. Kept separate from studentData()
     * so PublicStudentRegistrationService never has to guess which fields
     * belong to which model.
     */
    public function accountData(): array
    {
        return [
            'name' => trim($this->input('first_name').' '.$this->input('last_name')),
            'email' => $this->validated('email'),
            'password' => $this->validated('password'),
        ];
    }
}
```

- [ ] **Step 2: Rewrite `PublicStudentRegistrationService`**

```php
<?php

namespace App\Domain\Students\Services;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Students\Events\StudentPublicRegistrationCompleted;
use App\Domain\Students\Exceptions\DuplicateRegistration;
use App\Domain\Students\Exceptions\InvalidRegistrationLink;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Models\StudentRegistrationLink;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * The only place a public, unauthenticated visitor's request is allowed to
 * turn into a User+Student pair. Ordering matters and mirrors §68 of the
 * spec exactly: the token is validated and locked *before* TenantContext is
 * touched, so nothing here can ever be tricked into resolving a tenant from
 * anything the client sent directly.
 */
class PublicStudentRegistrationService
{
    public function __construct(
        private readonly StudentRegistrationLinkService $links,
        private readonly EnrollmentService $enrollment,
        private readonly EmailOtpService $otps,
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string}  $accountData
     * @param  array<string, mixed>  $studentData  Already validated by
     *                                              PublicStudentRegistrationRequest - never trusted for tenant_id/
     *                                              structure_id, which aren't even accepted fields on that request.
     *
     * @throws InvalidRegistrationLink
     * @throws DuplicateRegistration
     */
    public function register(string $plainToken, array $accountData, array $studentData): Student
    {
        // Validated once outside the transaction so a token that's
        // obviously wrong (unknown, expired, revoked) never even opens a
        // transaction or takes a row lock.
        $link = $this->links->validate($plainToken);

        try {
            return DB::transaction(function () use ($link, $accountData, $studentData) {
                // Re-fetch with a row lock: two concurrent requests can both
                // pass validate() above before either commits, so the real
                // "still usable" + "increment usage_count" check has to
                // happen against a locked row, not the copy validate()
                // already returned. See §50 of the original registration-
                // link spec.
                $locked = StudentRegistrationLink::query()
                    ->whereKey($link->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $locked->isUsable()) {
                    throw InvalidRegistrationLink::invalid();
                }

                TenantContext::set($locked->structure);

                if ($this->duplicateAccountEmail($accountData['email']) || $this->duplicateStudent($studentData)) {
                    throw new DuplicateRegistration;
                }

                $user = User::query()->create([
                    'name' => $accountData['name'],
                    'email' => $accountData['email'],
                    'password' => Hash::make($accountData['password']),
                ]);
                $user->assignRole('eleve');

                $student = $this->enrollment->register($studentData + [
                    'user_id' => $user->id,
                    'email' => $accountData['email'],
                ]);

                $locked->markUsed();

                $this->otps->generate($user);

                $this->audit->log('student.public_registration_completed', $student, [], [
                    'registration_link_id' => $locked->id,
                ]);

                StudentPublicRegistrationCompleted::dispatch($student);

                Log::info('student.public_registration.completed', [
                    'structure_id' => $locked->structure_id,
                    'registration_link_id' => $locked->id,
                    'student_id' => $student->id,
                ]);

                Auth::login($user);

                return $student;
            });
        } catch (InvalidRegistrationLink|DuplicateRegistration $e) {
            Log::info('student.public_registration.failed', [
                'registration_link_id' => $link->id,
                'reason' => $e instanceof InvalidRegistrationLink ? $e->reason : 'duplicate',
            ]);

            throw $e;
        } finally {
            TenantContext::clear();
        }
    }

    /**
     * Global, unscoped on purpose: a self-service account's email is its
     * login credential, so a duplicate anywhere (any tenant) must be
     * rejected - but the message never says which school the existing
     * account belongs to (§ edge cases: "sans révéler à quel établissement
     * ce compte est déjà rattaché").
     */
    private function duplicateAccountEmail(string $email): bool
    {
        return User::query()->withoutTenantScope()->where('email', $email)->exists();
    }

    /**
     * Scoped implicitly to the tenant just activated by TenantContext::set()
     * above - Student's BelongsToTenant global scope does the filtering.
     */
    private function duplicateStudent(array $data): bool
    {
        $email = $data['email'] ?? null;
        $phone = $data['phone'] ?? null;

        if (! $email && ! $phone) {
            return false;
        }

        return Student::query()
            ->where(function ($query) use ($email, $phone) {
                if ($email) {
                    $query->orWhere('email', $email);
                }

                if ($phone) {
                    $query->orWhere('phone', $phone);
                }
            })
            ->exists();
    }
}
```

- [ ] **Step 3: Update the controller**

```php
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
```

`success()` is removed - the flow now ends at `eleve.otp.show`, not a static confirmation page.

- [ ] **Step 4: Update the view - add password fields, drop nothing else**

Edit `resources/views/register/student.blade.php`: insert a password block right after the `email` field (before the `license_category`/`course_type` grid), and make `email`/`birth_date` required (remove "(optionnel)" from their labels, add `required`):

```blade
            <div>
                <x-input-label for="email" value="E-mail" />
                <x-text-input id="email" type="email" name="email" class="block mt-1 w-full" :value="old('email')" required />
                <x-input-error :messages="$errors->get('email')" class="mt-1" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="password" value="Mot de passe" />
                    <x-text-input id="password" type="password" name="password" class="block mt-1 w-full" required />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="password_confirmation" value="Confirmer le mot de passe" />
                    <x-text-input id="password_confirmation" type="password" name="password_confirmation" class="block mt-1 w-full" required />
                </div>
            </div>
```

And change the `birth_date` field's label from `"Date de naissance (optionnel)"` to `"Date de naissance"` with `required` added to its `<x-text-input>`.

- [ ] **Step 5: Delete the now-unused success view and route**

```bash
rm resources/views/register/student-success.blade.php
```

Edit `routes/web.php`, remove the `success` route from the `public-registration.` group:

```php
Route::prefix('register/student')
    ->name('public-registration.')
    ->group(function () {
        Route::get('/', [PublicStudentRegistrationController::class, 'show'])
            ->middleware('throttle:30,1')
            ->name('show');

        Route::post('/', [PublicStudentRegistrationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('store');
    });
```

(The `eleve.otp.show` route it now redirects to is added in Task 7 - until that task lands, this route doesn't exist yet, so don't run the feature test suite expecting `store()` to redirect successfully until Task 7 is done. Unit-level pieces of this task are still independently testable.)

- [ ] **Step 6: Pint**

```bash
vendor/bin/pint --dirty --format agent
```

Do not run the full test suite yet - `tests/Feature/Students/PublicStudentRegistrationTest.php` still targets the old single-`$data` signature and the now-removed `success` route; it's rewritten in Task 8, once Task 7's OTP routes exist for `store()` to redirect to.

- [ ] **Step 7: Commit**

```bash
git add app/Domain/Students/Http/Requests/PublicStudentRegistrationRequest.php app/Domain/Students/Services/PublicStudentRegistrationService.php app/Domain/Students/Http/Controllers/PublicStudentRegistrationController.php resources/views/register/student.blade.php routes/web.php
git rm resources/views/register/student-success.blade.php
git commit -m "feat(students): public registration creates a User account and sends an OTP"
```

---

## Task 7: OTP verification screen, `otp.verified` middleware, routes

**Files:**
- Create: `app/Domain/Students/Http/Middleware/EnsureEmailOtpVerified.php`
- Create: `app/Domain/Students/Http/Controllers/EmailOtpController.php`
- Create: `app/Domain/Students/Http/Requests/VerifyEmailOtpRequest.php`
- Create: `resources/views/eleve/verification-otp.blade.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`

**Interfaces:**
- Consumes: `EmailOtpService` (Task 3), `StudentEmailVerified` event (Task 5).
- Produces: routes `eleve.otp.show` / `eleve.otp.verify` / `eleve.otp.resend`; middleware alias `otp.verified`.

- [ ] **Step 1: Create the middleware**

```bash
php artisan make:middleware EnsureEmailOtpVerified --no-interaction
```

Move it to `app/Domain/Students/Http/Middleware/EnsureEmailOtpVerified.php` (matching `ResolveTenant`'s domain-namespaced location - the default `app/Http/Middleware` is unused elsewhere in this codebase) and update its namespace:

```php
<?php

namespace App\Domain\Students\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applied only to the eleve routes that require a verified account
 * (dashboard, planning, quiz, dossier) - not to the OTP screen's own routes,
 * or every request would redirect back to itself.
 */
class EnsureEmailOtpVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('eleve') && $user->email_verified_at === null) {
            return redirect()->route('eleve.otp.show');
        }

        return $next($request);
    }
}
```

- [ ] **Step 2: Register the middleware alias**

Edit `bootstrap/app.php`:

```php
use App\Domain\Students\Http\Middleware\EnsureEmailOtpVerified;
```

```php
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'otp.verified' => EnsureEmailOtpVerified::class,
        ]);
```

- [ ] **Step 3: Create the FormRequest**

```php
<?php

namespace App\Domain\Students\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'digits:6'],
        ];
    }
}
```

- [ ] **Step 4: Create the controller**

```php
<?php

namespace App\Domain\Students\Http\Controllers;

use App\Domain\Students\Events\StudentEmailVerified;
use App\Domain\Students\Exceptions\InvalidOtp;
use App\Domain\Students\Http\Requests\VerifyEmailOtpRequest;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\EmailOtpService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EmailOtpController extends Controller
{
    public function __construct(
        private readonly EmailOtpService $otps,
    ) {}

    public function show(): View|RedirectResponse
    {
        if (Auth::user()->email_verified_at !== null) {
            return redirect()->route('eleve.dashboard');
        }

        return view('eleve.verification-otp');
    }

    public function verify(VerifyEmailOtpRequest $request): RedirectResponse
    {
        $user = Auth::user();

        try {
            $this->otps->verify($user, $request->validated('code'));
        } catch (InvalidOtp $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        $student = Student::query()->where('user_id', $user->id)->firstOrFail();
        StudentEmailVerified::dispatch($student);

        return redirect()->route('eleve.dashboard')->with('status', 'Adresse e-mail vérifiée.');
    }

    public function resend(): RedirectResponse
    {
        $this->otps->generate(Auth::user());

        return back()->with('status', 'Un nouveau code a été envoyé.');
    }
}
```

- [ ] **Step 5: Create the view**

```blade
<x-guest-layout>
    <div class="mb-5 text-center">
        <p class="text-xs font-semibold text-primary tracking-wide uppercase mb-1">Vérification</p>
        <h1 class="text-lg font-semibold text-content">Confirmez votre adresse e-mail</h1>
        <p class="text-sm text-content-secondary mt-1">
            Saisissez le code à 6 chiffres envoyé à {{ auth()->user()->email }}.
        </p>
    </div>

    @if (session('status'))
        <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
    @endif

    <form method="POST" action="{{ route('eleve.otp.verify') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="code" value="Code de vérification" />
            <x-text-input id="code" name="code" inputmode="numeric" maxlength="6" class="block mt-1 w-full text-center tracking-[0.5em]" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-1" />
        </div>
        <x-primary-button class="w-full justify-center">Vérifier</x-primary-button>
    </form>

    <form method="POST" action="{{ route('eleve.otp.resend') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm text-primary hover:underline">Renvoyer le code</button>
    </form>
</x-guest-layout>
```

- [ ] **Step 6: Add the routes**

Edit `routes/web.php`. Add the OTP-only group (auth + role:eleve, no `otp.verified` - this is where the redirect lands), and add `otp.verified` to the existing eleve/quiz groups:

```php
use App\Domain\Students\Http\Controllers\EmailOtpController;
```

```php
Route::middleware(['auth', 'role:eleve'])
    ->prefix('eleve')
    ->name('eleve.')
    ->group(function () {
        Route::get('verification-otp', [EmailOtpController::class, 'show'])->name('otp.show');
        Route::post('verification-otp', [EmailOtpController::class, 'verify'])->name('otp.verify');
        Route::post('verification-otp/resend', [EmailOtpController::class, 'resend'])
            ->middleware('throttle:1,1')
            ->name('otp.resend');
    });
```

Change the existing eleve group's middleware and the quiz group's middleware to add `otp.verified`:

```php
Route::middleware(['auth', 'role:eleve', 'otp.verified'])
    ->prefix('quiz')
    ->name('quiz.')
    ->group(function () {
        Route::get('play', [QuizController::class, 'play'])->name('play');
        Route::get('/', [QuizController::class, 'index'])->name('index');
        Route::post('/', [QuizController::class, 'store'])->name('store');
        Route::get('results', [QuizController::class, 'results'])->name('results');
        Route::get('attempts/{attempt}', [QuizController::class, 'showAttempt'])->name('attempts.show');
    });
```

```php
Route::middleware(['auth', 'role:eleve', 'otp.verified'])
    ->name('eleve.')
    ->group(function () {
        Route::view('eleve/dashboard', 'eleve.dashboard')->name('dashboard');
        Route::get('eleve/planning', StudentPlanningController::class)->name('planning');
    });
```

(This second `eleve.` group is distinct from the OTP-only one above - Laravel allows the same route-name prefix across multiple `Route::group()` calls; this already happens elsewhere in this file, e.g. `finance.` and `training.` each appear more than once.)

- [ ] **Step 7: Pint**

```bash
vendor/bin/pint --dirty --format agent
```

- [ ] **Step 8: Commit**

```bash
git add app/Domain/Students/Http/Middleware/EnsureEmailOtpVerified.php app/Domain/Students/Http/Controllers/EmailOtpController.php app/Domain/Students/Http/Requests/VerifyEmailOtpRequest.php resources/views/eleve/verification-otp.blade.php bootstrap/app.php routes/web.php
git commit -m "feat(students): OTP verification screen and otp.verified middleware"
```

---

## Task 8: Rewrite the public-registration + OTP feature tests, full suite green

**Files:**
- Modify: `tests/Feature/Students/PublicStudentRegistrationTest.php`
- Create: `tests/Feature/Students/EmailOtpVerificationTest.php`

**Interfaces:**
- Consumes: everything from Tasks 1-7.

- [ ] **Step 1: Rewrite `PublicStudentRegistrationTest.php`**

Replace its content entirely - the golden path now asserts a `User` + `Student` pair, a redirect to `eleve.otp.show`, and an unverified email, instead of asserting a `Student` alone and a redirect to the removed `success` route:

```php
<?php

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Notifications\Notifications\AlertNotification;
use App\Domain\Students\Exceptions\InvalidRegistrationLink;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Models\StudentRegistrationLink;
use App\Domain\Students\Services\PublicStudentRegistrationService;
use App\Domain\Students\Services\StudentRegistrationLinkService;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->service = app(StudentRegistrationLinkService::class);

    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');

    ['token' => $this->token] = $this->service->generate($this->structure, $this->admin);
});

function validRegistrationPayload(string $token, array $overrides = []): array
{
    return array_merge([
        'registration_token' => $token,
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'phone' => '077112233',
        'email' => 'jean.dupont@example.com',
        'password' => 'Password!234',
        'password_confirmation' => 'Password!234',
        'birth_date' => '2000-01-01',
        'license_category' => 'B',
        'course_type' => 'normal',
    ], $overrides);
}

// --- Golden path -----------------------------------------------------

it('shows the tenant name on a valid token without asking the visitor to choose one', function () {
    $response = $this->get('/register/student?token='.$this->token);

    $response->assertOk();
    $response->assertSee($this->structure->name);
    $response->assertDontSee('Choisissez votre auto-école');
});

it('creates a User+Student pair, logs the visitor in, and sends them to OTP verification', function () {
    Notification::fake();
    Mail::fake();

    $response = $this->post('/register/student', validRegistrationPayload($this->token));

    $response->assertRedirect(route('eleve.otp.show'));
    $this->assertAuthenticated();

    $student = Student::withoutTenantScope()->where('phone', '077112233')->firstOrFail();
    expect($student->structure_id)->toBe($this->structure->id);
    expect($student->lifecycle_stage->value)->toBe('prospect');

    $user = User::withoutTenantScope()->where('email', 'jean.dupont@example.com')->firstOrFail();
    expect($user->structure_id)->toBe($this->structure->id);
    expect($user->email_verified_at)->toBeNull();
    expect($user->hasRole('eleve'))->toBeTrue();
    expect($student->user_id)->toBe($user->id);

    Mail::assertSent(\App\Domain\Students\Mail\EmailOtpMail::class);
    Notification::assertSentTo($this->admin, AlertNotification::class);
});

it('increments the link usage count and records last_used_at on success', function () {
    $this->post('/register/student', validRegistrationPayload($this->token));

    $link = StudentRegistrationLink::withoutTenantScope()->where('token_hash', hash('sha256', $this->token))->firstOrFail();
    expect($link->usage_count)->toBe(1);
    expect($link->last_used_at)->not->toBeNull();
});

it('writes an audit log entry for the completed registration', function () {
    $this->post('/register/student', validRegistrationPayload($this->token));

    $student = Student::withoutTenantScope()->where('phone', '077112233')->firstOrFail();

    $log = AuditLog::query()
        ->where('auditable_type', $student->getMorphClass())
        ->where('auditable_id', $student->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->action)->toBe('student.public_registration_completed');
});

// --- Invalid / expired / revoked tokens -------------------------------

it('shows an invalid-link state for an unknown token', function () {
    $response = $this->get('/register/student?token=not-a-real-token');

    $response->assertOk();
    $response->assertSee('invalide');
});

it('shows an invalid-link state when no token is provided at all', function () {
    $response = $this->get('/register/student');

    $response->assertOk();
    $response->assertSee('invalide');
});

it('rejects registration submitted with an unknown token', function () {
    $response = $this->post('/register/student', validRegistrationPayload('not-a-real-token'));

    $response->assertOk();
    expect(Student::withoutTenantScope()->where('phone', '077112233')->exists())->toBeFalse();
});

it('shows a distinct expired-link message and rejects registration', function () {
    StudentRegistrationLink::query()->update(['expires_at' => now()->subDay()]);

    $show = $this->get('/register/student?token='.$this->token);
    $show->assertSee('expiré');

    $this->post('/register/student', validRegistrationPayload($this->token));
    expect(Student::withoutTenantScope()->where('phone', '077112233')->exists())->toBeFalse();
});

it('rejects registration through a revoked link', function () {
    $link = StudentRegistrationLink::withoutTenantScope()->where('token_hash', hash('sha256', $this->token))->firstOrFail();
    $this->service->revoke($link);

    $this->post('/register/student', validRegistrationPayload($this->token));
    expect(Student::withoutTenantScope()->where('phone', '077112233')->exists())->toBeFalse();
});

it('rejects registration when the tenant is suspended', function () {
    $this->structure->update(['status' => StructureStatus::Suspended]);

    $this->post('/register/student', validRegistrationPayload($this->token));
    expect(Student::withoutTenantScope()->where('phone', '077112233')->exists())->toBeFalse();
});

// --- Duplicates --------------------------------------------------------

it('rejects a registration whose account email already exists, without naming the tenant it belongs to', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $existingUser = User::factory()->create(['structure_id' => $otherStructure->id, 'email' => 'jean.dupont@example.com']);

    $response = $this->post('/register/student', validRegistrationPayload($this->token, ['phone' => '099999999']));

    $response->assertOk();
    $response->assertDontSee($otherStructure->name);
    expect(Student::withoutTenantScope()->where('phone', '099999999')->exists())->toBeFalse();
});

it('never flashes the submitted password back after a duplicate rejection', function () {
    User::factory()->create(['structure_id' => $this->structure->id, 'email' => 'jean.dupont@example.com']);

    $this->post('/register/student', validRegistrationPayload($this->token, ['phone' => '099999999']));

    expect(session('_old_input.password') ?? null)->toBeNull();
});

it('rejects a registration whose phone already exists for the same tenant', function () {
    Student::factory()->create(['structure_id' => $this->structure->id, 'phone' => '077112233']);

    $response = $this->post('/register/student', validRegistrationPayload($this->token, ['email' => 'other@example.com']));

    $response->assertOk();
    expect(User::withoutTenantScope()->where('email', 'other@example.com')->exists())->toBeFalse();
});

it('allows the same email/phone to register in two different tenants', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $otherAdmin = User::factory()->create(['structure_id' => $otherStructure->id]);
    $otherAdmin->assignRole('admin');
    ['token' => $otherToken] = $this->service->generate($otherStructure, $otherAdmin);

    $this->post('/register/student', validRegistrationPayload($this->token));
    $this->post('/register/student', validRegistrationPayload($otherToken, ['email' => 'jean2@example.com']));

    expect(Student::withoutTenantScope()->where('phone', '077112233')->count())->toBe(2);
});

// --- §48: anti-tampering -------------------------------------------------

it('ignores a client-supplied tenant/structure id and always uses the token\'s own tenant', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);

    $payload = validRegistrationPayload($this->token) + [
        'tenant_id' => $otherStructure->id,
        'structure_id' => $otherStructure->id,
    ];

    $this->post('/register/student', $payload);

    $student = Student::withoutTenantScope()->where('phone', '077112233')->firstOrFail();
    expect($student->structure_id)->toBe($this->structure->id);
    expect($student->structure_id)->not->toBe($otherStructure->id);
});

// --- §49: IDOR across tenants via the public endpoint ---------------------

it('never lets token A resolve a resource belonging to tenant B', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $otherAdmin = User::factory()->create(['structure_id' => $otherStructure->id]);
    $otherAdmin->assignRole('admin');
    ['link' => $otherLink] = $this->service->generate($otherStructure, $otherAdmin);

    $this->post('/register/student', validRegistrationPayload($this->token));

    $student = Student::withoutTenantScope()->where('phone', '077112233')->firstOrFail();
    expect($student->structure_id)->not->toBe($otherStructure->id);
    expect($otherLink->structure_id)->toBe($otherStructure->id);
});

// --- §50: concurrency ------------------------------------------------

it('lets only one of two concurrent submissions succeed when max_uses is 1', function () {
    $link = StudentRegistrationLink::withoutTenantScope()->where('token_hash', hash('sha256', $this->token))->firstOrFail();
    $link->update(['max_uses' => 1]);

    $service = app(PublicStudentRegistrationService::class);

    $results = [];
    foreach ([1, 2] as $i) {
        try {
            $service->register(
                $this->token,
                ['name' => "Candidate Number $i", 'email' => "candidate{$i}@example.com", 'password' => 'Password!234'],
                [
                    'first_name' => 'Candidate',
                    'last_name' => "Number $i",
                    'phone' => "07700000$i",
                    'birth_date' => '2000-01-01',
                    'license_category' => 'B',
                    'course_type' => 'normal',
                ],
            );
            $results[] = 'ok';
        } catch (InvalidRegistrationLink) {
            $results[] = 'rejected';
        }
    }

    expect($results)->toBe(['ok', 'rejected']);
    expect(Student::withoutTenantScope()->where('structure_id', $this->structure->id)->count())->toBe(1);
    expect($link->fresh()->usage_count)->toBe(1);
});

// --- §51: rate limiting ------------------------------------------------

it('rate limits repeated public registration submissions from the same IP', function () {
    for ($i = 0; $i < 6; $i++) {
        $this->post('/register/student', validRegistrationPayload($this->token, ['phone' => "0771100{$i}0", 'email' => "u{$i}@example.com"]));
    }

    $response = $this->post('/register/student', validRegistrationPayload($this->token, ['phone' => '077110099', 'email' => 'ulast@example.com']));

    $response->assertStatus(429);
});

it('rate limits repeated token-validation lookups from the same IP', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->get('/register/student?token=guess-'.$i);
    }

    $response = $this->get('/register/student?token=guess-final');

    $response->assertStatus(429);
});

// --- Validation ---------------------------------------------------------

it('redirects back to the form with field errors when required data is missing', function () {
    $response = $this->from('/register/student?token='.$this->token)
        ->post('/register/student', ['registration_token' => $this->token]);

    $response->assertRedirect('/register/student?token='.$this->token);
    $response->assertSessionHasErrors(['first_name', 'last_name', 'phone', 'email', 'password', 'birth_date', 'license_category', 'course_type']);
    expect(Student::withoutTenantScope()->count())->toBe(0);
});
```

- [ ] **Step 2: Write `EmailOtpVerificationTest.php`**

```php
<?php

use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\EmailOtp;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    $this->user = User::factory()->create(['structure_id' => $this->structure->id, 'email_verified_at' => null]);
    $this->user->assignRole('eleve');
    $this->student = Student::factory()->create(['structure_id' => $this->structure->id, 'user_id' => $this->user->id]);
});

it('blocks an unverified eleve from a gated route and sends them to the OTP screen', function () {
    $response = $this->actingAs($this->user)->get(route('eleve.dashboard'));

    $response->assertRedirect(route('eleve.otp.show'));
});

it('lets a verified eleve reach gated routes normally', function () {
    $this->user->forceFill(['email_verified_at' => now()])->save();

    $this->actingAs($this->user)->get(route('eleve.dashboard'))->assertOk();
});

it('verifies a correct code and advances the student straight to dossier setup', function () {
    Mail::fake();
    $code = app(\App\Domain\Students\Services\EmailOtpService::class)->generate($this->user);

    $response = $this->actingAs($this->user)->post(route('eleve.otp.verify'), ['code' => $code]);

    $response->assertRedirect(route('eleve.dashboard'));
    expect($this->user->fresh()->email_verified_at)->not->toBeNull();
    expect($this->student->fresh()->lifecycle_stage)->toBe(LifecycleStage::DossierSetup);
});

it('rejects a wrong code with a field error and does not verify the account', function () {
    Mail::fake();
    app(\App\Domain\Students\Services\EmailOtpService::class)->generate($this->user);

    $response = $this->actingAs($this->user)->post(route('eleve.otp.verify'), ['code' => '000000']);

    $response->assertSessionHasErrors('code');
    expect($this->user->fresh()->email_verified_at)->toBeNull();
});

it('lets the user resend a code, throttled to once per minute', function () {
    Mail::fake();

    $this->actingAs($this->user)->post(route('eleve.otp.resend'))->assertRedirect();
    $response = $this->actingAs($this->user)->post(route('eleve.otp.resend'));

    $response->assertStatus(429);
});

it('locks out after 5 wrong attempts even with the eventual right code', function () {
    Mail::fake();
    $code = app(\App\Domain\Students\Services\EmailOtpService::class)->generate($this->user);

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($this->user)->post(route('eleve.otp.verify'), ['code' => '000000']);
    }

    $response = $this->actingAs($this->user)->post(route('eleve.otp.verify'), ['code' => $code]);

    $response->assertSessionHasErrors('code');
    expect($this->user->fresh()->email_verified_at)->toBeNull();
});
```

- [ ] **Step 3: Run the full suite**

```bash
php artisan test --compact
```

Expected: PASS, no red tests anywhere (this also catches any place `LifecycleStage::Enrollment`/`PublicStudentRegistrationService::register()`'s old signature was relied on elsewhere - e.g. `tests/Feature/Students/StudentRegistrationLinkAdminTest.php` doesn't call `register()` directly, so it should be unaffected, but re-run it explicitly if anything fails).

- [ ] **Step 4: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add tests/Feature/Students/PublicStudentRegistrationTest.php tests/Feature/Students/EmailOtpVerificationTest.php
git commit -m "test(students): adapt registration tests to the User+OTP flow, add OTP coverage"
```

---

## Task 9: `RequiredDocumentType` model, migration, factory, policy

**Files:**
- Create: `database/migrations/xxxx_create_required_document_types_table.php`
- Create: `app/Domain/Students/Models/RequiredDocumentType.php`
- Create: `app/Domain/Students/Database/Factories/RequiredDocumentTypeFactory.php`
- Create: `app/Domain/Students/Policies/RequiredDocumentTypePolicy.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Test: `tests/Unit/Students/RequiredDocumentTypePolicyTest.php`

**Interfaces:**
- Produces: `RequiredDocumentType` model with `structure_id, label, position, is_active`; scopes `active()`, `ordered()`.

- [ ] **Step 1: Migration**

```bash
php artisan make:migration create_required_document_types_table --no-interaction
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
        Schema::create('required_document_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('structure_id')->constrained('structures')->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['structure_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('required_document_types');
    }
};
```

- [ ] **Step 2: Model**

```php
<?php

namespace App\Domain\Students\Models;

use App\Domain\Students\Database\Factories\RequiredDocumentTypeFactory;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequiredDocumentType extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return RequiredDocumentTypeFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'label',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position');
    }
}
```

- [ ] **Step 3: Factory**

```php
<?php

namespace App\Domain\Students\Database\Factories;

use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequiredDocumentType>
 */
class RequiredDocumentTypeFactory extends Factory
{
    protected $model = RequiredDocumentType::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'label' => $this->faker->randomElement(["Carte d'identité", 'Justificatif de domicile', 'Photo d\'identité']),
            'position' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
```

- [ ] **Step 4: Write the failing policy test**

```php
<?php

use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
});

it('lets an admin manage a required document type belonging to their own tenant', function () {
    $type = RequiredDocumentType::factory()->create(['structure_id' => $this->structure->id]);

    expect($this->admin->can('viewAny', RequiredDocumentType::class))->toBeTrue();
    expect($this->admin->can('create', RequiredDocumentType::class))->toBeTrue();
    expect($this->admin->can('update', $type))->toBeTrue();
});

it('denies an admin from updating another tenant\'s required document type', function () {
    $otherStructure = Structure::factory()->create();
    $type = RequiredDocumentType::factory()->create(['structure_id' => $otherStructure->id]);

    expect($this->admin->can('update', $type))->toBeFalse();
});

it('denies a non-admin role entirely', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    expect($moniteur->can('viewAny', RequiredDocumentType::class))->toBeFalse();
});
```

- [ ] **Step 5: Run it, confirm it fails**

```bash
php artisan test --compact --filter=RequiredDocumentTypePolicyTest
```

Expected: FAIL - Policy class doesn't exist / not registered.

- [ ] **Step 6: Create the policy**

```php
<?php

namespace App\Domain\Students\Policies;

use App\Domain\Students\Models\RequiredDocumentType;
use App\Models\User;

class RequiredDocumentTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, RequiredDocumentType $type): bool
    {
        return $user->hasRole('admin') && $type->structure_id === $user->structure_id;
    }
}
```

- [ ] **Step 7: Register the policy**

Edit `app/Providers/AppServiceProvider.php`:

```php
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Policies\RequiredDocumentTypePolicy;
```

```php
        Gate::policy(RequiredDocumentType::class, RequiredDocumentTypePolicy::class);
```

- [ ] **Step 8: Run, confirm PASS; migrate; Pint; commit**

```bash
php artisan migrate --no-interaction
php artisan test --compact --filter=RequiredDocumentTypePolicyTest
vendor/bin/pint --dirty --format agent
git add database/migrations app/Domain/Students/Models/RequiredDocumentType.php app/Domain/Students/Database/Factories/RequiredDocumentTypeFactory.php app/Domain/Students/Policies/RequiredDocumentTypePolicy.php app/Providers/AppServiceProvider.php tests/Unit/Students/RequiredDocumentTypePolicyTest.php
git commit -m "feat(students): add RequiredDocumentType model and its tenant-scoped policy"
```

---

## Task 10: Admin CRUD for required document types

**Files:**
- Create: `app/Domain/Students/Http/Requests/StoreRequiredDocumentTypeRequest.php`
- Create: `app/Domain/Students/Http/Requests/UpdateRequiredDocumentTypeRequest.php`
- Create: `app/Domain/Students/Http/Controllers/RequiredDocumentTypeController.php`
- Create: `resources/views/settings/document-types.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/partials/sidebar-nav.blade.php`
- Test: `tests/Feature/Students/RequiredDocumentTypeAdminTest.php`

**Interfaces:**
- Consumes: `RequiredDocumentType` (Task 9).
- Produces: routes `settings.document-types.{index,store,update}`.

- [ ] **Step 1: FormRequests**

```php
<?php

namespace App\Domain\Students\Http\Requests;

use App\Domain\Students\Models\RequiredDocumentType;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequiredDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', RequiredDocumentType::class);
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:150'],
        ];
    }
}
```

```php
<?php

namespace App\Domain\Students\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequiredDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('requiredDocumentType'));
    }

    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
```

- [ ] **Step 2: Controller**

```php
<?php

namespace App\Domain\Students\Http\Controllers;

use App\Domain\Students\Http\Requests\StoreRequiredDocumentTypeRequest;
use App\Domain\Students\Http\Requests\UpdateRequiredDocumentTypeRequest;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RequiredDocumentTypeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', RequiredDocumentType::class);

        return view('settings.document-types', [
            'types' => RequiredDocumentType::query()->ordered()->get(),
        ]);
    }

    public function store(StoreRequiredDocumentTypeRequest $request): RedirectResponse
    {
        RequiredDocumentType::query()->create($request->validated() + [
            'position' => (int) RequiredDocumentType::query()->max('position') + 1,
        ]);

        return back()->with('status', 'Pièce requise ajoutée.');
    }

    public function update(UpdateRequiredDocumentTypeRequest $request, RequiredDocumentType $requiredDocumentType): RedirectResponse
    {
        $requiredDocumentType->update($request->validated());

        return back()->with('status', 'Pièce requise mise à jour.');
    }
}
```

- [ ] **Step 3: Routes**

Edit `routes/web.php`, inside the existing `settings.` group (after the `student-registration` sub-group):

```php
use App\Domain\Students\Http\Controllers\RequiredDocumentTypeController;
```

```php
        Route::prefix('documents-requis')
            ->name('document-types.')
            ->group(function () {
                Route::get('/', [RequiredDocumentTypeController::class, 'index'])->name('index');
                Route::post('/', [RequiredDocumentTypeController::class, 'store'])->name('store');
                Route::patch('{requiredDocumentType}', [RequiredDocumentTypeController::class, 'update'])->name('update');
            });
```

- [ ] **Step 4: View**

```blade
<x-app-layout>
    <x-slot name="header">Pièces requises</x-slot>

    <div class="py-6 space-y-6 max-w-3xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        <x-card>
            <h2 class="text-sm font-semibold text-content mb-3">Ajouter une pièce</h2>
            <form method="POST" action="{{ route('settings.document-types.store') }}" class="flex gap-3">
                @csrf
                <x-text-input name="label" class="flex-1" placeholder="Ex. Carte d'identité" required />
                <x-primary-button>Ajouter</x-primary-button>
            </form>
        </x-card>

        <x-card>
            <h2 class="text-sm font-semibold text-content mb-3">Pièces configurées</h2>
            <div class="divide-y divide-surface-inset">
                @forelse ($types as $type)
                    <div class="flex items-center justify-between py-3">
                        <span class="text-sm text-content {{ $type->is_active ? '' : 'line-through text-content-muted' }}">
                            {{ $type->label }}
                        </span>
                        <form method="POST" action="{{ route('settings.document-types.update', $type) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_active" value="{{ $type->is_active ? '0' : '1' }}">
                            <button type="submit" class="text-xs text-primary hover:underline">
                                {{ $type->is_active ? 'Désactiver' : 'Réactiver' }}
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-content-secondary py-3">Aucune pièce configurée pour le moment.</p>
                @endforelse
            </div>
        </x-card>
    </div>
</x-app-layout>
```

- [ ] **Step 5: Nav link**

Edit `resources/views/layouts/partials/sidebar-nav.blade.php`, in the "Administration" block, right after the `settings.student-registration.*` link:

```blade
                    <x-sidebar-link :href="route('settings.document-types.index')" :active="request()->routeIs('settings.document-types.*')" icon="clipboard-list">Pièces requises</x-sidebar-link>
```

- [ ] **Step 6: Write and run the feature test**

```php
<?php

use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
});

it('lets an admin add a required document type', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('settings.document-types.store'), ['label' => "Carte d'identité"]);

    $response->assertRedirect();
    expect(RequiredDocumentType::query()->where('label', "Carte d'identité")->where('structure_id', $this->structure->id)->exists())->toBeTrue();
});

it('lets an admin deactivate a required document type without deleting it', function () {
    $type = RequiredDocumentType::factory()->create(['structure_id' => $this->structure->id]);

    $this->actingAs($this->admin)->patch(route('settings.document-types.update', $type), ['is_active' => '0']);

    expect($type->fresh()->is_active)->toBeFalse();
    expect(RequiredDocumentType::query()->whereKey($type->id)->exists())->toBeTrue();
});

it('never lets an admin update another tenant\'s required document type', function () {
    $otherStructure = Structure::factory()->create();
    $type = RequiredDocumentType::factory()->create(['structure_id' => $otherStructure->id]);

    $this->actingAs($this->admin)
        ->patch(route('settings.document-types.update', $type), ['is_active' => '0'])
        ->assertForbidden();

    expect($type->fresh()->is_active)->toBeTrue();
});

it('denies a non-admin role', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->get(route('settings.document-types.index'))->assertForbidden();
});
```

```bash
php artisan test --compact --filter=RequiredDocumentTypeAdminTest
```

Expected: PASS.

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Domain/Students/Http/Requests/StoreRequiredDocumentTypeRequest.php app/Domain/Students/Http/Requests/UpdateRequiredDocumentTypeRequest.php app/Domain/Students/Http/Controllers/RequiredDocumentTypeController.php resources/views/settings/document-types.blade.php routes/web.php resources/views/layouts/partials/sidebar-nav.blade.php tests/Feature/Students/RequiredDocumentTypeAdminTest.php
git commit -m "feat(students): admin CRUD for per-tenant required document types"
```

---

## Task 11: Extend `Document` with review fields

**Files:**
- Create: `database/migrations/xxxx_add_review_fields_to_documents_table.php`
- Create: `app/Domain/Documents/Enums/DocumentReviewStatus.php`
- Modify: `app/Domain/Documents/Models/Document.php`
- Modify: `app/Domain/Documents/Services/DocumentService.php`
- Test: `tests/Feature/Documents/DocumentUploadTest.php` (add coverage for the new optional param - do not remove any existing test)

**Interfaces:**
- Produces: `Document::requiredDocumentType(): BelongsTo`, `Document::reviewedBy(): BelongsTo`; `DocumentService::upload(..., ?RequiredDocumentType $requiredDocumentType = null): Document` (new trailing optional param - fully backward compatible with the two existing call sites in `DocumentController`).

- [ ] **Step 1: Migration**

```bash
php artisan make:migration add_review_fields_to_documents_table --no-interaction
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
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('required_document_type_id')->nullable()->after('documentable_id')
                ->constrained('required_document_types')->nullOnDelete();
            $table->string('review_status')->default('pending')->after('is_current');
            $table->text('rejection_reason')->nullable()->after('review_status');
            $table->foreignId('reviewed_by_id')->nullable()->after('rejection_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('required_document_type_id');
            $table->dropConstrainedForeignId('reviewed_by_id');
            $table->dropColumn(['review_status', 'rejection_reason', 'reviewed_at']);
        });
    }
};
```

- [ ] **Step 2: Enum**

```php
<?php

namespace App\Domain\Documents\Enums;

enum DocumentReviewStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Approved => 'Approuvé',
            self::Rejected => 'Rejeté',
        };
    }
}
```

- [ ] **Step 3: Update the `Document` model**

```php
<?php

namespace App\Domain\Documents\Models;

use App\Domain\Documents\Database\Factories\DocumentFactory;
use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Enums\DocumentType;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Models\User;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return DocumentFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'uploaded_by',
        'documentable_type',
        'documentable_id',
        'type',
        'disk',
        'path',
        'original_name',
        'version',
        'is_current',
        'expires_at',
        'required_document_type_id',
        'review_status',
        'rejection_reason',
        'reviewed_by_id',
        'reviewed_at',
    ];

    protected $casts = [
        'type' => DocumentType::class,
        'is_current' => 'boolean',
        'expires_at' => 'date',
        'review_status' => DocumentReviewStatus::class,
        'reviewed_at' => 'datetime',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function requiredDocumentType(): BelongsTo
    {
        return $this->belongsTo(RequiredDocumentType::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }
}
```

(`App\Domain\Documents` depending on `App\Domain\Students` is explicitly allowed - see `tests/Architecture/DomainBoundariesTest.php`'s `'Documents domain only depends on Students and Fleet among business domains'` rule.)

- [ ] **Step 4: Write the failing test for the new `DocumentService::upload()` param**

Add to `tests/Feature/Documents/DocumentUploadTest.php` (append, don't remove existing tests):

```php
it('versions dossier documents by required_document_type_id, not by DocumentType, and resets review status', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);
    $requiredType = RequiredDocumentType::factory()->create(['structure_id' => $structure->id]);

    $service = app(DocumentService::class);

    $first = $service->upload(
        UploadedFile::fake()->create('id-card.pdf', 10),
        $student,
        DocumentType::Other,
        null,
        null,
        $requiredType,
    );

    expect($first->review_status)->toBe(DocumentReviewStatus::Pending);

    $first->update(['review_status' => DocumentReviewStatus::Approved, 'reviewed_at' => now()]);

    $second = $service->upload(
        UploadedFile::fake()->create('id-card-v2.pdf', 10),
        $student,
        DocumentType::Other,
        null,
        null,
        $requiredType,
    );

    expect($first->fresh()->is_current)->toBeFalse();
    expect($second->is_current)->toBeTrue();
    expect($second->version)->toBe(2);
    expect($second->review_status)->toBe(DocumentReviewStatus::Pending);
    expect($second->required_document_type_id)->toBe($requiredType->id);
});
```

Add the needed `use` statements (`App\Domain\Documents\Enums\DocumentReviewStatus`, `App\Domain\Students\Models\RequiredDocumentType`, `Illuminate\Http\UploadedFile`) at the top of the file alongside the existing ones.

- [ ] **Step 5: Run it, confirm it fails**

```bash
php artisan test --compact --filter=DocumentUploadTest
```

Expected: FAIL - `DocumentService::upload()` doesn't accept a 6th argument yet.

- [ ] **Step 6: Update `DocumentService`**

```php
<?php

namespace App\Domain\Documents\Services;

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Enums\DocumentType;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the legacy pattern of loose file-path columns on `eleves`
 * (photo, cni_path, justif_domicile...) - no history, and re-uploading a
 * document silently threw the old file away. Every upload here becomes a
 * new version; the previous one is kept, just no longer flagged current.
 *
 * When $requiredDocumentType is given (student dossier uploads), the
 * "previous version" lookup keys on required_document_type_id instead of
 * DocumentType - several dossier pieces can share the same generic
 * DocumentType::Other, so type alone can't tell them apart. Passing it also
 * resets review_status to Pending on the new row: a fresh upload always
 * needs a fresh review, even if the version it replaces was Approved.
 */
class DocumentService
{
    public function upload(
        UploadedFile $file,
        Model $documentable,
        DocumentType $type,
        ?User $uploadedBy = null,
        ?string $expiresAt = null,
        ?RequiredDocumentType $requiredDocumentType = null,
    ): Document {
        return DB::transaction(function () use ($file, $documentable, $type, $uploadedBy, $expiresAt, $requiredDocumentType) {
            $query = Document::query()
                ->where('documentable_type', $documentable->getMorphClass())
                ->where('documentable_id', $documentable->getKey())
                ->where('is_current', true);

            $query = $requiredDocumentType
                ? $query->where('required_document_type_id', $requiredDocumentType->id)
                : $query->where('type', $type->value);

            $previous = $query->first();

            $previous?->update(['is_current' => false]);

            $path = $file->store('documents', 'local');

            return Document::query()->create([
                'documentable_type' => $documentable->getMorphClass(),
                'documentable_id' => $documentable->getKey(),
                'type' => $type->value,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'version' => ($previous?->version ?? 0) + 1,
                'is_current' => true,
                'uploaded_by' => $uploadedBy?->id,
                'expires_at' => $expiresAt,
                'required_document_type_id' => $requiredDocumentType?->id,
                'review_status' => DocumentReviewStatus::Pending,
            ]);
        });
    }
}
```

- [ ] **Step 7: Run it, confirm it passes; run the whole Documents suite too**

```bash
php artisan test --compact --filter=DocumentUploadTest
php artisan test --compact tests/Feature/Documents
```

Expected: PASS, including the two pre-existing tests (`storeForStudent`/`storeForVehicle` still call `upload()` with 5 args, which still works since the 6th is optional).

- [ ] **Step 8: Migrate, Pint, commit**

```bash
php artisan migrate --no-interaction
vendor/bin/pint --dirty --format agent
git add database/migrations app/Domain/Documents/Enums/DocumentReviewStatus.php app/Domain/Documents/Models/Document.php app/Domain/Documents/Services/DocumentService.php tests/Feature/Documents/DocumentUploadTest.php
git commit -m "feat(documents): add per-document review fields for the dossier workflow"
```

---

## Task 12: Eleve "Constitution du dossier" screen

**Files:**
- Create: `app/Domain/Students/Http/Requests/UploadDossierDocumentRequest.php`
- Create: `app/Domain/Students/Http/Controllers/StudentDossierController.php`
- Create: `resources/views/eleve/dossier.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/partials/sidebar-nav.blade.php`
- Test: `tests/Feature/Students/StudentDossierTest.php`

**Interfaces:**
- Consumes: `DocumentService::upload()` (Task 11), `LifecycleService::transitionTo()`.
- Produces: routes `eleve.dossier.{show,upload,submit}`.

- [ ] **Step 1: FormRequest**

```php
<?php

namespace App\Domain\Students\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDossierDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('eleve') ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,webp'],
        ];
    }
}
```

(Mirrors `StoreDocumentRequest`'s existing `max:5120`/`mimes` rule exactly, for consistency.)

- [ ] **Step 2: Controller**

```php
<?php

namespace App\Domain\Students\Http\Controllers;

use App\Domain\Documents\Enums\DocumentType;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Services\DocumentService;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Exceptions\InvalidStageTransition;
use App\Domain\Students\Http\Requests\UploadDossierDocumentRequest;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\LifecycleService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentDossierController extends Controller
{
    public function __construct(
        private readonly DocumentService $documents,
        private readonly LifecycleService $lifecycle,
    ) {}

    public function show(): View
    {
        $student = $this->currentStudent();

        $types = RequiredDocumentType::query()->active()->ordered()->get();

        $current = Document::query()
            ->where('documentable_type', $student->getMorphClass())
            ->where('documentable_id', $student->id)
            ->where('is_current', true)
            ->whereNotNull('required_document_type_id')
            ->get()
            ->keyBy('required_document_type_id');

        return view('eleve.dossier', [
            'student' => $student,
            'types' => $types,
            'documentsByType' => $current,
            'canSubmit' => $types->every(fn ($type) => $current->has($type->id)),
        ]);
    }

    public function upload(UploadDossierDocumentRequest $request, RequiredDocumentType $requiredDocumentType): RedirectResponse
    {
        $student = $this->currentStudent();

        abort_unless($requiredDocumentType->structure_id === $student->structure_id, 404);

        $this->documents->upload(
            $request->file('file'),
            $student,
            DocumentType::Other,
            Auth::user(),
            null,
            $requiredDocumentType,
        );

        return back()->with('status', 'Document déposé.');
    }

    public function submit(): RedirectResponse
    {
        $student = $this->currentStudent();

        $missing = RequiredDocumentType::query()->active()->get()->reject(
            fn (RequiredDocumentType $type) => Document::query()
                ->where('documentable_type', $student->getMorphClass())
                ->where('documentable_id', $student->id)
                ->where('required_document_type_id', $type->id)
                ->exists()
        );

        if ($missing->isNotEmpty()) {
            return back()->withErrors(['dossier' => 'Merci de déposer toutes les pièces requises avant de soumettre votre dossier.']);
        }

        try {
            $this->lifecycle->transitionTo($student, LifecycleStage::Validation);
        } catch (InvalidStageTransition) {
            return back()->withErrors(['dossier' => 'Votre dossier n\'est pas dans un état permettant la soumission.']);
        }

        return redirect()->route('eleve.dossier.show')->with('status', 'Dossier soumis pour revue.');
    }

    private function currentStudent(): Student
    {
        return Student::query()->where('user_id', Auth::id())->firstOrFail();
    }
}
```

- [ ] **Step 3: View**

```blade
<x-app-layout>
    <x-slot name="header">Mon dossier</x-slot>

    <div class="py-6 space-y-4 max-w-3xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        @if ($errors->has('dossier'))
            <x-alert variant="danger">{{ $errors->first('dossier') }}</x-alert>
        @endif

        <x-card>
            <div class="divide-y divide-surface-inset">
                @forelse ($types as $type)
                    @php($document = $documentsByType->get($type->id))
                    <div class="py-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-content">{{ $type->label }}</span>
                            @if ($document)
                                <span @class([
                                    'text-xs font-semibold px-2 py-0.5 rounded-full',
                                    'bg-warning/10 text-warning' => $document->review_status->value === 'pending',
                                    'bg-success/10 text-success' => $document->review_status->value === 'approved',
                                    'bg-danger/10 text-danger' => $document->review_status->value === 'rejected',
                                ])>{{ $document->review_status->label() }}</span>
                            @else
                                <span class="text-xs text-content-muted">Rien déposé</span>
                            @endif
                        </div>

                        @if ($document?->review_status->value === 'rejected')
                            <p class="text-xs text-danger mt-1">{{ $document->rejection_reason }}</p>
                        @endif

                        <form method="POST" action="{{ route('eleve.dossier.upload', $type) }}" enctype="multipart/form-data" class="mt-2 flex gap-2">
                            @csrf
                            <input type="file" name="file" class="text-xs flex-1" required>
                            <x-primary-button class="text-xs">{{ $document ? 'Redéposer' : 'Déposer' }}</x-primary-button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-content-secondary py-3">Aucune pièce requise pour le moment.</p>
                @endforelse
            </div>
        </x-card>

        <form method="POST" action="{{ route('eleve.dossier.submit') }}">
            @csrf
            <x-primary-button class="w-full justify-center" @disabled(! $canSubmit)>
                Soumettre mon dossier
            </x-primary-button>
        </form>
    </div>
</x-app-layout>
```

- [ ] **Step 4: Routes**

Edit `routes/web.php`, add to the `otp.verified`-gated `eleve.` group:

```php
use App\Domain\Students\Http\Controllers\StudentDossierController;
```

```php
        Route::get('eleve/dossier', [StudentDossierController::class, 'show'])->name('dossier.show');
        Route::post('eleve/dossier/submit', [StudentDossierController::class, 'submit'])->name('dossier.submit');
        Route::post('eleve/dossier/{requiredDocumentType}', [StudentDossierController::class, 'upload'])->name('dossier.upload');
```

(The `submit` route is declared before the `{requiredDocumentType}` wildcard route so `/eleve/dossier/submit` doesn't get swallowed by the route-model-binding parameter.)

- [ ] **Step 5: Nav link**

Edit `resources/views/layouts/partials/sidebar-nav.blade.php`, in the eleve `@elseif` block:

```blade
                <x-sidebar-link :href="route('eleve.dossier.show')" :active="request()->routeIs('eleve.dossier.*')" icon="clipboard-list">Mon dossier</x-sidebar-link>
```

- [ ] **Step 6: Write and run the feature test**

```php
<?php

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    $this->user = User::factory()->create(['structure_id' => $this->structure->id, 'email_verified_at' => now()]);
    $this->user->assignRole('eleve');
    $this->student = Student::factory()->stage(LifecycleStage::DossierSetup)->create([
        'structure_id' => $this->structure->id,
        'user_id' => $this->user->id,
    ]);
    $this->type = RequiredDocumentType::factory()->create(['structure_id' => $this->structure->id]);
});

it('lets an eleve upload a required document', function () {
    $response = $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $this->type),
        ['file' => UploadedFile::fake()->create('id.pdf', 10)],
    );

    $response->assertRedirect();
    $document = Document::query()->where('required_document_type_id', $this->type->id)->firstOrFail();
    expect($document->review_status)->toBe(DocumentReviewStatus::Pending);
    expect($document->documentable_id)->toBe($this->student->id);
});

it('blocks submission until every active required type has a document', function () {
    $this->actingAs($this->user)->post(route('eleve.dossier.submit'))->assertSessionHasErrors('dossier');

    expect($this->student->fresh()->lifecycle_stage)->toBe(LifecycleStage::DossierSetup);
});

it('submits the dossier and transitions to Validation once every piece is present', function () {
    $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $this->type),
        ['file' => UploadedFile::fake()->create('id.pdf', 10)],
    );

    $this->actingAs($this->user)->post(route('eleve.dossier.submit'))->assertRedirect();

    expect($this->student->fresh()->lifecycle_stage)->toBe(LifecycleStage::Validation);
});

it('resets a rejected document to pending and re-links the dossier when re-submitted', function () {
    $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $this->type),
        ['file' => UploadedFile::fake()->create('id.pdf', 10)],
    );
    $this->actingAs($this->user)->post(route('eleve.dossier.submit'));

    $document = Document::query()->where('required_document_type_id', $this->type->id)->where('is_current', true)->firstOrFail();
    $document->update(['review_status' => DocumentReviewStatus::Rejected, 'rejection_reason' => 'Illisible']);
    $this->student->refresh();
    app(\App\Domain\Students\Services\LifecycleService::class)->transitionTo($this->student, LifecycleStage::DossierSetup);

    $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $this->type),
        ['file' => UploadedFile::fake()->create('id-v2.pdf', 10)],
    );

    $new = Document::query()->where('required_document_type_id', $this->type->id)->where('is_current', true)->firstOrFail();
    expect($new->review_status)->toBe(DocumentReviewStatus::Pending);
    expect($document->fresh()->is_current)->toBeFalse();
});

it('never lets an eleve upload against another tenant\'s required document type', function () {
    $otherStructure = Structure::factory()->create();
    $otherType = RequiredDocumentType::factory()->create(['structure_id' => $otherStructure->id]);

    $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $otherType),
        ['file' => UploadedFile::fake()->create('id.pdf', 10)],
    )->assertNotFound();
});
```

```bash
php artisan test --compact --filter=StudentDossierTest
```

Expected: PASS.

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Domain/Students/Http/Requests/UploadDossierDocumentRequest.php app/Domain/Students/Http/Controllers/StudentDossierController.php resources/views/eleve/dossier.blade.php routes/web.php resources/views/layouts/partials/sidebar-nav.blade.php tests/Feature/Students/StudentDossierTest.php
git commit -m "feat(students): eleve self-service dossier submission screen"
```

---

## Task 13: Admin dossier review queue

**Files:**
- Modify: `app/Domain/Documents/Policies/DocumentPolicy.php`
- Create: `app/Domain/Documents/Http/Requests/RejectDossierDocumentRequest.php`
- Create: `app/Domain/Documents/Http/Controllers/DocumentReviewController.php`
- Create: `resources/views/students/dossier-review.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/partials/sidebar-nav.blade.php`
- Test: `tests/Feature/Documents/DocumentReviewTest.php`

**Interfaces:**
- Consumes: `LifecycleService::transitionTo()`, `RequiredDocumentType::active()`.
- Produces: routes `dossiers.index`, `documents.approve`, `documents.reject`.

- [ ] **Step 1: Add a `review` ability to `DocumentPolicy`**

```php
<?php

namespace App\Domain\Documents\Policies;

use App\Domain\Documents\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Document $document): bool
    {
        return $user->hasAnyRole(['admin', 'moniteur']) && $document->structure_id === $user->structure_id;
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasRole('admin') && $document->structure_id === $user->structure_id;
    }

    public function review(User $user, Document $document): bool
    {
        return $user->hasRole('admin') && $document->structure_id === $user->structure_id;
    }
}
```

(No new `Gate::policy()` registration needed - `DocumentPolicy` is already registered for `Document::class` in `AppServiceProvider`.)

- [ ] **Step 2: `RejectDossierDocumentRequest`**

```php
<?php

namespace App\Domain\Documents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectDossierDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('review', $this->route('document'));
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
```

- [ ] **Step 3: `DocumentReviewController`**

```php
<?php

namespace App\Domain\Documents\Http\Controllers;

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Http\Requests\RejectDossierDocumentRequest;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\LifecycleService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DocumentReviewController extends Controller
{
    public function __construct(
        private readonly LifecycleService $lifecycle,
    ) {}

    public function index(): View
    {
        $students = Student::query()
            ->where('lifecycle_stage', LifecycleStage::Validation->value)
            ->with(['documents' => fn ($query) => $query->where('is_current', true)->whereNotNull('required_document_type_id')->with('requiredDocumentType')])
            ->get();

        return view('students.dossier-review', ['students' => $students]);
    }

    public function approve(Document $document): RedirectResponse
    {
        $this->authorize('review', $document);

        $this->decide($document, DocumentReviewStatus::Approved);

        return back()->with('status', 'Document approuvé.');
    }

    public function reject(RejectDossierDocumentRequest $request, Document $document): RedirectResponse
    {
        $this->decide($document, DocumentReviewStatus::Rejected, $request->validated('reason'));

        return back()->with('status', 'Document rejeté.');
    }

    private function decide(Document $document, DocumentReviewStatus $status, ?string $reason = null): void
    {
        $student = $document->documentable;

        abort_unless($student instanceof Student && $student->lifecycle_stage === LifecycleStage::Validation, 403);

        $document->update([
            'review_status' => $status,
            'rejection_reason' => $reason,
            'reviewed_by_id' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        if ($status === DocumentReviewStatus::Rejected) {
            $this->lifecycle->transitionTo($student, LifecycleStage::DossierSetup);

            return;
        }

        $activeTypeIds = RequiredDocumentType::query()->where('structure_id', $student->structure_id)->active()->pluck('id');

        $allApproved = $activeTypeIds->isEmpty() ? false : $activeTypeIds->every(
            fn (int $typeId) => Document::query()
                ->where('documentable_type', $student->getMorphClass())
                ->where('documentable_id', $student->id)
                ->where('required_document_type_id', $typeId)
                ->where('is_current', true)
                ->where('review_status', DocumentReviewStatus::Approved)
                ->exists()
        );

        if ($allApproved) {
            $this->lifecycle->transitionTo($student, LifecycleStage::Enrollment);
        }
    }
}
```

`Document` isn't itself tenant-scoped away here since `documentable`/`Student::query()` calls happen with `TenantContext` active during a normal authenticated admin request (`ResolveTenant` middleware), so both queries are already implicitly scoped - consistent with how every other admin controller in this codebase relies on the same middleware.

- [ ] **Step 4: View**

```blade
<x-app-layout>
    <x-slot name="header">Dossiers en attente de revue</x-slot>

    <div class="py-6 space-y-4 max-w-4xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif

        @forelse ($students as $student)
            <x-card>
                <h2 class="text-sm font-semibold text-content mb-3">{{ $student->fullName() }}</h2>
                <div class="divide-y divide-surface-inset">
                    @foreach ($student->documents as $document)
                        <div class="py-3 flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm text-content">{{ $document->requiredDocumentType?->label }}</p>
                                <p class="text-xs text-content-muted">{{ $document->original_name }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('documents.approve', $document) }}">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-success hover:underline">Approuver</button>
                                </form>
                                <form method="POST" action="{{ route('documents.reject', $document) }}" onsubmit="return confirm('Motif du rejet ?');">
                                    @csrf
                                    <input type="hidden" name="reason" value="Document non conforme">
                                    <button type="submit" class="text-xs font-semibold text-danger hover:underline">Rejeter</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @empty
            <x-card>
                <p class="text-sm text-content-secondary">Aucun dossier en attente de revue.</p>
            </x-card>
        @endforelse
    </div>
</x-app-layout>
```

(The reject form's hardcoded `reason` is a v1 placeholder - a proper reason-input modal is a natural follow-up once this screen is verified working end-to-end; it's out of this plan's scope since the design spec only requires the reason field to be mandatory and shown to the student, which it already is.)

- [ ] **Step 5: Routes**

Edit `routes/web.php`, inside the existing `role:admin` group used for `students.documents.store`/`fleet.documents.store`:

```php
use App\Domain\Documents\Http\Controllers\DocumentReviewController;
```

```php
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::post('students/{student}/documents', [DocumentController::class, 'storeForStudent'])->name('students.documents.store');
    Route::post('fleet/{vehicle}/documents', [DocumentController::class, 'storeForVehicle'])->name('fleet.documents.store');
    Route::get('dossiers', [DocumentReviewController::class, 'index'])->name('dossiers.index');
    Route::post('documents/{document}/approve', [DocumentReviewController::class, 'approve'])->name('documents.approve');
    Route::post('documents/{document}/reject', [DocumentReviewController::class, 'reject'])->name('documents.reject');
});
```

- [ ] **Step 6: Nav link**

Edit `resources/views/layouts/partials/sidebar-nav.blade.php`, in the "Gestion" block (admin-visible):

```blade
                <x-sidebar-link :href="route('dossiers.index')" :active="request()->routeIs('dossiers.*')" icon="clipboard-list">Dossiers en attente</x-sidebar-link>
```

- [ ] **Step 7: Write and run the feature test**

```php
<?php

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');

    $this->type = RequiredDocumentType::factory()->create(['structure_id' => $this->structure->id]);
    $this->student = Student::factory()->stage(LifecycleStage::Validation)->create(['structure_id' => $this->structure->id]);
    $this->document = Document::factory()->create([
        'structure_id' => $this->structure->id,
        'documentable_type' => Student::class,
        'documentable_id' => $this->student->id,
        'required_document_type_id' => $this->type->id,
        'review_status' => DocumentReviewStatus::Pending,
        'is_current' => true,
    ]);
});

it('sends the student back to dossier setup when the only document is rejected', function () {
    $this->actingAs($this->admin)->post(route('documents.reject', $this->document), ['reason' => 'Illisible']);

    expect($this->document->fresh()->review_status)->toBe(DocumentReviewStatus::Rejected);
    expect($this->document->fresh()->rejection_reason)->toBe('Illisible');
    expect($this->student->fresh()->lifecycle_stage)->toBe(LifecycleStage::DossierSetup);
});

it('advances the student to enrollment once every active required document is approved', function () {
    $this->actingAs($this->admin)->post(route('documents.approve', $this->document));

    expect($this->document->fresh()->review_status)->toBe(DocumentReviewStatus::Approved);
    expect($this->student->fresh()->lifecycle_stage)->toBe(LifecycleStage::Enrollment);
});

it('does not advance the student while another required document is still pending', function () {
    $secondType = RequiredDocumentType::factory()->create(['structure_id' => $this->structure->id]);
    Document::factory()->create([
        'structure_id' => $this->structure->id,
        'documentable_type' => Student::class,
        'documentable_id' => $this->student->id,
        'required_document_type_id' => $secondType->id,
        'review_status' => DocumentReviewStatus::Pending,
        'is_current' => true,
    ]);

    $this->actingAs($this->admin)->post(route('documents.approve', $this->document));

    expect($this->student->fresh()->lifecycle_stage)->toBe(LifecycleStage::Validation);
});

it('refuses a review action when the student is no longer at the Validation stage', function () {
    $this->student->setLifecycleStage(LifecycleStage::Enrollment);
    $this->student->save();

    $this->actingAs($this->admin)->post(route('documents.approve', $this->document))->assertForbidden();

    expect($this->document->fresh()->review_status)->toBe(DocumentReviewStatus::Pending);
});

it('never lets an admin review another tenant\'s document', function () {
    $otherStructure = Structure::factory()->create();
    $otherAdmin = User::factory()->create(['structure_id' => $otherStructure->id]);
    $otherAdmin->assignRole('admin');

    $this->actingAs($otherAdmin)->post(route('documents.approve', $this->document))->assertForbidden();

    expect($this->document->fresh()->review_status)->toBe(DocumentReviewStatus::Pending);
});

it('denies a non-admin role from reviewing documents', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->post(route('documents.approve', $this->document))->assertForbidden();
});
```

```bash
php artisan test --compact --filter=DocumentReviewTest
```

Expected: PASS.

- [ ] **Step 8: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Domain/Documents/Policies/DocumentPolicy.php app/Domain/Documents/Http/Requests/RejectDossierDocumentRequest.php app/Domain/Documents/Http/Controllers/DocumentReviewController.php resources/views/students/dossier-review.blade.php routes/web.php resources/views/layouts/partials/sidebar-nav.blade.php tests/Feature/Documents/DocumentReviewTest.php
git commit -m "feat(documents): admin per-document dossier review with automatic stage transitions"
```

---

## Task 14: End-to-end test, full suite, architecture tests

**Files:**
- Create: `tests/Feature/Students/DossierEndToEndTest.php`

**Interfaces:**
- Consumes: every piece built in Tasks 1-13.

- [ ] **Step 1: Write the golden-path end-to-end test**

```php
<?php

use App\Domain\Documents\Models\Document;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\EmailOtpService;
use App\Domain\Students\Services\StudentRegistrationLinkService;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

it('walks a prospective student from public registration to Enrollment through every automatic transition', function () {
    Storage::fake('local');
    Mail::fake();

    $this->seed(RoleSeeder::class);
    $structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $admin = User::factory()->create(['structure_id' => $structure->id]);
    $admin->assignRole('admin');

    $type = RequiredDocumentType::factory()->create(['structure_id' => $structure->id, 'label' => "Carte d'identité"]);

    ['token' => $token] = app(StudentRegistrationLinkService::class)->generate($structure, $admin);

    // 1. Public self-registration.
    $this->post('/register/student', [
        'registration_token' => $token,
        'first_name' => 'Awa',
        'last_name' => 'Ndong',
        'email' => 'awa.ndong@example.com',
        'password' => 'Password!234',
        'password_confirmation' => 'Password!234',
        'phone' => '077998877',
        'birth_date' => '2001-03-15',
        'license_category' => 'B',
        'course_type' => 'normal',
    ])->assertRedirect(route('eleve.otp.show'));

    $user = User::withoutTenantScope()->where('email', 'awa.ndong@example.com')->firstOrFail();
    $student = Student::withoutTenantScope()->where('user_id', $user->id)->firstOrFail();
    expect($student->lifecycle_stage)->toBe(LifecycleStage::Prospect);

    // 2. OTP verification.
    $code = app(EmailOtpService::class)->generate($user);
    $this->actingAs($user)->post(route('eleve.otp.verify'), ['code' => $code])
        ->assertRedirect(route('eleve.dashboard'));

    expect($student->fresh()->lifecycle_stage)->toBe(LifecycleStage::DossierSetup);

    // 3. Dossier submission.
    $this->actingAs($user)->post(
        route('eleve.dossier.upload', $type),
        ['file' => UploadedFile::fake()->create('id.pdf', 10)],
    )->assertRedirect();

    $this->actingAs($user)->post(route('eleve.dossier.submit'))->assertRedirect();

    expect($student->fresh()->lifecycle_stage)->toBe(LifecycleStage::Validation);

    // 4. Admin approves the only required document.
    $document = Document::query()->where('required_document_type_id', $type->id)->where('is_current', true)->firstOrFail();
    $this->actingAs($admin)->post(route('documents.approve', $document))->assertRedirect();

    expect($student->fresh()->lifecycle_stage)->toBe(LifecycleStage::Enrollment);
});

it('sends a rejected document back through the loop before reaching Enrollment', function () {
    Storage::fake('local');
    Mail::fake();

    $this->seed(RoleSeeder::class);
    $structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $admin = User::factory()->create(['structure_id' => $structure->id]);
    $admin->assignRole('admin');
    $type = RequiredDocumentType::factory()->create(['structure_id' => $structure->id]);

    ['token' => $token] = app(StudentRegistrationLinkService::class)->generate($structure, $admin);

    $this->post('/register/student', [
        'registration_token' => $token,
        'first_name' => 'Awa',
        'last_name' => 'Ndong',
        'email' => 'awa2@example.com',
        'password' => 'Password!234',
        'password_confirmation' => 'Password!234',
        'phone' => '077998866',
        'birth_date' => '2001-03-15',
        'license_category' => 'B',
        'course_type' => 'normal',
    ]);

    $user = User::withoutTenantScope()->where('email', 'awa2@example.com')->firstOrFail();
    $student = Student::withoutTenantScope()->where('user_id', $user->id)->firstOrFail();
    $code = app(EmailOtpService::class)->generate($user);
    $this->actingAs($user)->post(route('eleve.otp.verify'), ['code' => $code]);

    $this->actingAs($user)->post(route('eleve.dossier.upload', $type), ['file' => UploadedFile::fake()->create('id.pdf', 10)]);
    $this->actingAs($user)->post(route('eleve.dossier.submit'));

    $document = Document::query()->where('required_document_type_id', $type->id)->where('is_current', true)->firstOrFail();
    $this->actingAs($admin)->post(route('documents.reject', $document), ['reason' => 'Illisible']);

    expect($student->fresh()->lifecycle_stage)->toBe(LifecycleStage::DossierSetup);

    // Re-upload only the rejected piece, resubmit, get approved.
    $this->actingAs($user)->post(route('eleve.dossier.upload', $type), ['file' => UploadedFile::fake()->create('id-v2.pdf', 10)]);
    $this->actingAs($user)->post(route('eleve.dossier.submit'));

    expect($student->fresh()->lifecycle_stage)->toBe(LifecycleStage::Validation);

    $newDocument = Document::query()->where('required_document_type_id', $type->id)->where('is_current', true)->firstOrFail();
    $this->actingAs($admin)->post(route('documents.approve', $newDocument));

    expect($student->fresh()->lifecycle_stage)->toBe(LifecycleStage::Enrollment);
});
```

- [ ] **Step 2: Run it**

```bash
php artisan test --compact --filter=DossierEndToEndTest
```

Expected: PASS.

- [ ] **Step 3: Run the entire suite, including architecture tests**

```bash
php artisan test --compact
```

Expected: PASS, zero red - this is the checkpoint that confirms `DomainBoundariesTest`'s `Documents depends only on Students and Fleet` rule still holds after Task 11's new `Document → RequiredDocumentType` relation, and that nothing in Tasks 1-13 introduced a stray dependency the architecture tests would catch.

- [ ] **Step 4: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add tests/Feature/Students/DossierEndToEndTest.php
git commit -m "test(students): end-to-end coverage for the full registration-to-enrollment flow"
```

---

## Task 15: Documentation

**Files:**
- Modify: `docs/features/student-public-registration.md`

**Interfaces:** none - documentation only.

- [ ] **Step 1: Rewrite the "Workflow integration" section**

Replace the existing paragraph (which describes the old one-shot `EnrollmentService::register()`-only flow ending at `Prospect`/`Incomplete`) with a description of the new multi-step flow: account creation → OTP → `DossierSetup` → self-service document submission → per-document admin review → automatic `Enrollment`. Link to `docs/superpowers/specs/2026-08-23-inscription-eleve-otp-dossier-design.md` as the source design doc, the same way the file already links to `docs/audit/multi-tenancy-audit.md` elsewhere.

Add two new subsections mirroring the existing style (`## Token lifecycle`, `## Anti-enumeration`, etc.):

```markdown
## Email OTP verification

A freshly self-registered account is created with `email_verified_at = null`
and immediately logged in, but `otp.verified` middleware blocks every other
eleve route (dashboard, planning, quiz, dossier) until the 6-digit code sent
to the account's email is confirmed. Codes are stored hashed (`sha256`,
mirroring `StudentRegistrationLink::token_hash`), expire after
`EMAIL_OTP_EXPIRY_MINUTES` (default 10), and lock out after
`EMAIL_OTP_MAX_ATTEMPTS` wrong guesses (default 5) - at that point the only
way forward is a resend, itself throttled to once per minute.

Verifying dispatches `StudentEmailVerified`, whose listener
(`ActivateStudentAfterEmailVerification`) chains two automatic
`LifecycleService::transitionTo()` calls with no visible intermediate state:
`Prospect → PreEnrollment → DossierSetup`.

## Dossier: required documents and per-document review

Each tenant configures its own list of required pieces
(`RequiredDocumentType`, admin-managed at **Paramètres → Pièces requises**).
A student at `DossierSetup` uploads one file per active required type
(`eleve/dossier`); each upload versions the existing `Document` row exactly
like every other document upload in this app (`DocumentService::upload()`,
extended with an optional `$requiredDocumentType` param that keys the
"previous version" lookup on `required_document_type_id` instead of
`DocumentType`, since dossier pieces share the generic `DocumentType::Other`).

"Soumettre mon dossier" transitions the student to `Validation` - server-side
gated on every active required type having at least one uploaded version,
regardless of its review status.

Review happens **per document**, not per dossier: an admin approves or
rejects each one individually from the `dossiers` queue (students currently
at `Validation`) or from a student's own profile. Rejecting one immediately
sends the student back to `DossierSetup` (`Validation → DossierSetup`),
regardless of the other documents' state; approving the *last* remaining
pending/rejected active-type document advances the student to `Enrollment`.
Both directions are refused server-side (403) if the student isn't currently
at `Validation` when the review action runs - this is enforced in
`DocumentReviewController::decide()`, not just hidden in the UI.
```

- [ ] **Step 2: Commit**

```bash
git add docs/features/student-public-registration.md
git commit -m "docs(students): document the OTP + dossier review flow"
```

---

## Final checklist

- [ ] `php artisan test --compact` - full suite green.
- [ ] `vendor/bin/pint --format agent` (no `--dirty`, whole tree) - clean.
- [ ] Manually walk the flow once in a real browser (per this project's UI-verification convention): generate a link as admin, register publicly, verify OTP, upload/submit a dossier, approve it as admin, confirm the student lands on `Enrollment`.
