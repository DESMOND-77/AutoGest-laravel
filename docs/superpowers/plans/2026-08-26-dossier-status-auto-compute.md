# Automatic Dossier Status + Submission Bundle Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the manual admin-driven `dossier_status` transitions (built in a prior task, now known to be the wrong design) with automatic computation from the student's document state, reorder `DossierStatus` to `Incomplete → Complete → Validated → Submitted`, and add a submission bundle: when a student submits their dossier, every current required-piece document is zipped into an archive, a `document_submitted` flag flips to true, and that flag resets to false whenever the school adds a new required document type.

**Architecture:** `DossierStatusService` changes from a guarded manual-transition gateway (`transitionTo()`, throwing `InvalidDossierTransition`) to a pure recomputation gateway (`syncFor(Student $student): Student`) that derives the correct status from `RequiredDocumentType`/`Document` state and the new `document_submitted` flag, and writes it via the existing `Student::setDossierStatus()` bypass — never any other way. It is called from every place a student's document state can change: document upload (`DocumentService::upload()`), document review (`DocumentReviewController::decide()`), a new required type being added or an existing one updated (`RequiredDocumentTypeController`), and dossier submission (`StudentDossierController::submit()`). A new `DocumentBundleService` (Students domain) builds the ZIP archive using PHP's `ZipArchive` (confirmed available: `php -m` lists `zip`).

**Tech Stack:** Laravel 12, PHP 8.5, Pest 3, Blade, PHP `ZipArchive`.

**Spec:** No separate spec document — scoped directly from the user's correction of `Promptset/03-revue-dossier-admin.md`'s original (wrong) manual-transition design, given verbatim in French: dossier status must be computed from document state (uploaded vs. approved), and submission bundles documents into a ZIP while flipping a `document_submitted` flag that resets when the school adds a new required piece.

## Global Constraints

- `dossier_status` is written **only** via `Student::setDossierStatus()`, called **only** from `DossierStatusService`. Never assign it through `$fillable`/mass update.
- `document_submitted` and `documents_zip_path` are written **only** via `Student::setDocumentSubmitted()`/`setDocumentsZipPath()` (new bypass setters, same pattern as `setDossierStatus()`) — never through `$fillable`.
- Status computation order, exactly: if `document_submitted` is true → `Submitted`. Else if there are no active `RequiredDocumentType` rows for the tenant → `Incomplete`. Else if any active required type has no current document → `Incomplete`. Else if any current document for an active required type is not `DocumentReviewStatus::Approved` → `Complete`. Else → `Validated`.
- `document_submitted` resets to `false` (and `documents_zip_path` to `null`) for **every** student in the tenant when a **new** `RequiredDocumentType` is created — not on update/deactivation of an existing one.
- The existing `StudentDossierController::submit()` lifecycle-stage behavior (advancing `DossierSetup → Validation → Enrollment` once every piece is approved) stays exactly as-is; the bundle/flag logic is added to the same action, not a separate one.
- `App\Domain\Documents` may depend on `App\Domain\Students` (existing, allowed rule in `tests/Architecture/DomainBoundariesTest.php`: "Documents domain only depends on Students and Fleet among business domains") — so `DocumentService` and `DocumentReviewController` (both in Documents) may inject `App\Domain\Students\Services\DossierStatusService` directly. Do not add a new architecture rule; do not introduce a Students → Documents dependency beyond what already exists (`StudentDossierController` already imports Documents-domain classes, so `DossierStatusService` referencing `Document`/`RequiredDocumentType` models is consistent with existing precedent).
- No arrow glyphs (←/→) in any UI copy.
- No destructive migrations — the new migration only adds columns.
- Every change must have a passing Pest test. Run `vendor/bin/pint --dirty --format agent` after PHP edits.

---

### Task 1: Add `document_submitted`/`documents_zip_path` and Student model support

**Files:**
- Create: a new migration via `php artisan make:migration add_document_submission_fields_to_students_table --table=students`
- Modify: `app/Domain/Students/Models/Student.php`

**Interfaces:**
- Produces: `Student::setDocumentSubmitted(bool $submitted): void`, `Student::setDocumentsZipPath(?string $path): void`, both readable normally afterward (`$student->document_submitted`, `$student->documents_zip_path`) since they're just guarded *writes*, not hidden attributes.

- [ ] **Step 1: Create and write the migration**

Run: `php artisan make:migration add_document_submission_fields_to_students_table --table=students --no-interaction`

Edit the generated file's `up()`:

```php
public function up(): void
{
    Schema::table('students', function (Blueprint $table) {
        $table->boolean('document_submitted')->default(false)->after('dossier_status');
        $table->string('documents_zip_path')->nullable()->after('document_submitted');
    });
}
```

And `down()`:

```php
public function down(): void
{
    Schema::table('students', function (Blueprint $table) {
        $table->dropColumn(['document_submitted', 'documents_zip_path']);
    });
}
```

- [ ] **Step 2: Run the migration against the test DB**

Per this project's standing DB-safety rule: run migrations against `TEST_DB_DATABASE` (the connection Pest already uses), never against the main dev DB directly. Run: `php artisan migrate --env=testing` if the project's test setup requires an explicit migrate step, or simply let Pest's `RefreshDatabase`/migration-on-boot handle it — check `phpunit.xml`/`tests/Pest.php` for the existing convention other migrations in this repo already follow, and do the same. Confirm with: `php artisan test --compact tests/Feature/Students/StudentTenantIsolationTest.php` (an unrelated, cheap Students test) still passes after the migration exists, proving schema is valid.

- [ ] **Step 3: Add casts and bypass setters to `Student`**

Edit `app/Domain/Students/Models/Student.php`. Add to the `casts()` array:

```php
'document_submitted' => 'boolean',
```

Add two new methods, placed after `setDossierStatus()`:

```php
/**
 * Bypasses $fillable on purpose - call only from DossierStatusService
 * (dossier submission) or RequiredDocumentTypeController (reset on a new
 * required piece being added).
 */
public function setDocumentSubmitted(bool $submitted): void
{
    $this->setAttribute('document_submitted', $submitted);
}

/**
 * Bypasses $fillable on purpose - same callers as setDocumentSubmitted().
 */
public function setDocumentsZipPath(?string $path): void
{
    $this->setAttribute('documents_zip_path', $path);
}
```

Do **not** add `document_submitted`/`documents_zip_path` to `$fillable` — they must stay guarded, exactly like `dossier_status`/`lifecycle_stage`.

- [ ] **Step 4: Commit**

```bash
git add database/migrations app/Domain/Students/Models/Student.php
git commit -m "feat(students): add document_submitted and documents_zip_path columns"
```

---

### Task 2: Reorder `DossierStatus`, remove the manual-transition machinery, rewrite `DossierStatusService` as pure computation

**Files:**
- Modify: `app/Domain/Students/Enums/DossierStatus.php`
- Delete: `app/Domain/Students/Exceptions/InvalidDossierTransition.php`
- Modify: `app/Domain/Students/Services/DossierStatusService.php`
- Delete and replace: `tests/Unit/Students/DossierStatusServiceTest.php`
- Modify: `app/Domain/Students/Http/Controllers/StudentController.php` (remove the manual `updateDossierStatus()` action added in a prior task)
- Delete: `app/Domain/Students/Http/Requests/UpdateDossierStatusRequest.php`
- Modify: `routes/web.php` (remove the `students.dossier-status` route)
- Modify: `resources/views/students/show.blade.php` (remove the manual "next status" buttons; keep a read-only status pill row)
- Delete: `tests/Feature/Students/DossierStatusTransitionTest.php` (tested the now-removed manual-transition HTTP endpoint entirely)

**Interfaces:**
- Consumes: `App\Domain\Students\Models\RequiredDocumentType` (existing, `active()` scope), `App\Domain\Documents\Models\Document` (existing), `App\Domain\Documents\Enums\DocumentReviewStatus` (existing).
- Produces: `DossierStatusService::syncFor(Student $student): Student` — the ONLY entry point later tasks call to keep `dossier_status` correct.

- [ ] **Step 1: Reorder and simplify `DossierStatus`**

Replace the full content of `app/Domain/Students/Enums/DossierStatus.php`:

```php
<?php

namespace App\Domain\Students\Enums;

/**
 * dossier_status is purely computed, not manually transitioned - see
 * DossierStatusService::syncFor(). Order: Incomplete -> Complete ->
 * Validated -> Submitted.
 */
enum DossierStatus: string
{
    case Incomplete = 'incomplete';
    case Complete = 'complete';
    case Validated = 'validated';
    case Submitted = 'submitted';

    public function label(): string
    {
        return match ($this) {
            self::Incomplete => 'Incomplet',
            self::Complete => 'Complet',
            self::Validated => 'Validé',
            self::Submitted => 'Soumis',
        };
    }
}
```

(`allowedNextStages()`/`canTransitionTo()` are gone — there is no manual transition anymore.)

- [ ] **Step 2: Delete the now-unused exception**

```bash
rm app/Domain/Students/Exceptions/InvalidDossierTransition.php
```

- [ ] **Step 3: Rewrite `DossierStatusService`**

Replace the full content of `app/Domain/Students/Services/DossierStatusService.php`:

```php
<?php

namespace App\Domain\Students\Services;

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Enums\DossierStatus;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;

/**
 * The only place allowed to change a student's dossier_status. Unlike
 * lifecycle_stage (a manually-triggered, guarded state machine), dossier_status
 * is purely derived from document state: call syncFor() after anything that
 * could change the answer (a document is uploaded, a document is
 * approved/rejected, a required type is added/updated, or the dossier is
 * submitted) and it recomputes and persists the correct value.
 */
class DossierStatusService
{
    public function syncFor(Student $student): Student
    {
        $target = $this->computeStatus($student);

        if ($student->dossier_status !== $target) {
            $student->setDossierStatus($target);
            $student->save();
        }

        return $student;
    }

    private function computeStatus(Student $student): DossierStatus
    {
        if ($student->document_submitted) {
            return DossierStatus::Submitted;
        }

        $types = RequiredDocumentType::query()->active()->get();

        if ($types->isEmpty()) {
            return DossierStatus::Incomplete;
        }

        $documents = Document::query()
            ->where('documentable_type', $student->getMorphClass())
            ->where('documentable_id', $student->id)
            ->where('is_current', true)
            ->whereNotNull('required_document_type_id')
            ->get()
            ->keyBy('required_document_type_id');

        $allUploaded = $types->every(fn (RequiredDocumentType $type) => $documents->has($type->id));

        if (! $allUploaded) {
            return DossierStatus::Incomplete;
        }

        $allApproved = $types->every(
            fn (RequiredDocumentType $type) => $documents->get($type->id)->review_status === DocumentReviewStatus::Approved
        );

        return $allApproved ? DossierStatus::Validated : DossierStatus::Complete;
    }
}
```

- [ ] **Step 4: Replace the unit test**

Replace the full content of `tests/Unit/Students/DossierStatusServiceTest.php`:

```php
<?php

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Enums\DocumentType;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Enums\DossierStatus;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\DossierStatusService;
use App\Domain\Tenancy\Models\Structure;

function makeDocument(Student $student, RequiredDocumentType $type, DocumentReviewStatus $status): Document
{
    return Document::factory()->create([
        'structure_id' => $student->structure_id,
        'documentable_type' => $student->getMorphClass(),
        'documentable_id' => $student->id,
        'type' => DocumentType::Other,
        'is_current' => true,
        'required_document_type_id' => $type->id,
        'review_status' => $status,
    ]);
}

it('is incomplete when no required document types exist', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);

    (new DossierStatusService)->syncFor($student);

    expect($student->fresh()->dossier_status)->toBe(DossierStatus::Incomplete);
});

it('is incomplete when a required type has no uploaded document', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);
    RequiredDocumentType::factory()->create(['structure_id' => $structure->id]);

    (new DossierStatusService)->syncFor($student);

    expect($student->fresh()->dossier_status)->toBe(DossierStatus::Incomplete);
});

it('is complete when every required document is uploaded but not all approved', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);
    $type = RequiredDocumentType::factory()->create(['structure_id' => $structure->id]);
    makeDocument($student, $type, DocumentReviewStatus::Pending);

    (new DossierStatusService)->syncFor($student);

    expect($student->fresh()->dossier_status)->toBe(DossierStatus::Complete);
});

it('is validated when every required document is uploaded and approved', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);
    $type = RequiredDocumentType::factory()->create(['structure_id' => $structure->id]);
    makeDocument($student, $type, DocumentReviewStatus::Approved);

    (new DossierStatusService)->syncFor($student);

    expect($student->fresh()->dossier_status)->toBe(DossierStatus::Validated);
});

it('is submitted once document_submitted is true, regardless of document state', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);
    $student->setDocumentSubmitted(true);
    $student->save();

    (new DossierStatusService)->syncFor($student);

    expect($student->fresh()->dossier_status)->toBe(DossierStatus::Submitted);
});

it('drops back to incomplete once a rejected document makes the dossier no longer fully uploaded is false, but stays complete if still all uploaded', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);
    $type = RequiredDocumentType::factory()->create(['structure_id' => $structure->id]);
    makeDocument($student, $type, DocumentReviewStatus::Rejected);

    (new DossierStatusService)->syncFor($student);

    expect($student->fresh()->dossier_status)->toBe(DossierStatus::Complete);
});
```

- [ ] **Step 5: Remove the manual admin-transition HTTP endpoint**

Edit `app/Domain/Students/Http/Controllers/StudentController.php`: delete the entire `updateDossierStatus()` method (it currently sits right before `createAccount()`), and remove the now-unused imports `App\Domain\Students\Exceptions\InvalidDossierTransition`, `App\Domain\Students\Http\Requests\UpdateDossierStatusRequest`, and `App\Domain\Students\Services\DossierStatusService` from this controller's `use` list **only if** nothing else in the file still needs `DossierStatusService` (it doesn't, after this deletion — check before removing). Remove the `private readonly DossierStatusService $dossier` constructor property too.

```bash
rm app/Domain/Students/Http/Requests/UpdateDossierStatusRequest.php
```

Edit `routes/web.php`: remove this block entirely:

```php
Route::middleware(['auth', 'role:admin'])
    ->patch('students/{student}/dossier-status', [StudentController::class, 'updateDossierStatus'])
    ->name('students.dossier-status');
```

```bash
rm tests/Feature/Students/DossierStatusTransitionTest.php
```

- [ ] **Step 6: Replace the manual buttons in the student profile view with a read-only status display**

Edit `resources/views/students/show.blade.php`. Find the block added in the prior task (status pill row + `@can('update', $student)` next-status buttons, inside the Documents tab's first `<x-card>`). Replace the whole `@can(...) ... @endcan` block (the "next status" buttons form loop) with nothing — delete it entirely, keeping only the status label + pill row above it:

```blade
<x-card>
    <div class="flex items-center justify-between mb-3">
        <div class="text-sm font-semibold text-content">Dossier administratif</div>
        <x-badge variant="info">{{ $student->dossier_status->label() }}</x-badge>
    </div>

    <ol class="flex flex-wrap gap-1.5">
        @foreach (\App\Domain\Students\Enums\DossierStatus::cases() as $status)
            <li @class([
                'px-2.5 py-1 rounded-ui-md text-xs font-medium',
                'bg-primary text-primary-content' => $status === $student->dossier_status,
                'bg-surface-inset text-content-secondary' => $status !== $student->dossier_status,
            ])>
                {{ $status->label() }}
            </li>
        @endforeach
    </ol>
</x-card>
```

(Task 4 will add a download-the-bundle link to this same card once the ZIP feature exists — leave a mental note, no code needed yet.)

- [ ] **Step 7: Run tests, format, commit**

Run: `php artisan test --compact tests/Unit/Students/DossierStatusServiceTest.php`
Expected: 6 passed.

Run: `php artisan test --compact tests/Feature/Students` (confirms the deleted route/view changes didn't break anything else in this domain)
Expected: all passed.

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Students/Enums/DossierStatus.php \
        app/Domain/Students/Services/DossierStatusService.php \
        app/Domain/Students/Http/Controllers/StudentController.php \
        routes/web.php \
        resources/views/students/show.blade.php \
        tests/Unit/Students/DossierStatusServiceTest.php
git rm app/Domain/Students/Exceptions/InvalidDossierTransition.php \
       app/Domain/Students/Http/Requests/UpdateDossierStatusRequest.php \
       tests/Feature/Students/DossierStatusTransitionTest.php
git commit -m "refactor(students): make dossier_status purely computed, drop manual transitions"
```

---

### Task 3: Wire automatic recomputation into document upload, review, and required-type changes

**Files:**
- Modify: `app/Domain/Documents/Services/DocumentService.php`
- Modify: `app/Domain/Documents/Http/Controllers/DocumentReviewController.php`
- Modify: `app/Domain/Students/Http/Controllers/RequiredDocumentTypeController.php`
- Test: `tests/Feature/Students/DossierStatusAutoComputeTest.php` (new)

**Interfaces:**
- Consumes: `App\Domain\Students\Services\DossierStatusService::syncFor(Student $student): Student` (from Task 2).

- [ ] **Step 1: Recompute after every document upload, when the documentable is a Student**

Edit `app/Domain/Documents/Services/DocumentService.php`. Add the import `use App\Domain\Students\Models\Student;` and `use App\Domain\Students\Services\DossierStatusService;`, inject the service, and call it at the end of `upload()`, still inside the same `DB::transaction()` closure:

```php
class DocumentService
{
    public function __construct(
        private readonly DossierStatusService $dossierStatus,
    ) {}

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

            $document = Document::query()->create([
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

            if ($documentable instanceof Student) {
                $this->dossierStatus->syncFor($documentable);
            }

            return $document;
        });
    }
}
```

(Keep every other line of the file exactly as it was - this only adds the constructor, the two imports, and the `if ($documentable instanceof Student)` block plus renaming the final `return Document::query()->create(...)` to assign `$document` first.)

- [ ] **Step 2: Recompute after a document review decision**

Edit `app/Domain/Documents/Http/Controllers/DocumentReviewController.php`. Add the import `use App\Domain\Students\Services\DossierStatusService;`, inject it, and call `syncFor()` at the end of `decide()`:

```php
class DocumentReviewController extends Controller
{
    public function __construct(
        private readonly DossierStatusService $dossierStatus,
    ) {}

    // ...index(), approve(), reject() unchanged...

    private function decide(Document $document, DocumentReviewStatus $status, ?string $reason = null): void
    {
        $student = $document->documentable;

        abort_unless($student instanceof Student && $student->lifecycle_stage === LifecycleStage::DossierSetup, 403);
        abort_unless($document->required_document_type_id !== null, 404);

        $document->update([
            'review_status' => $status,
            'rejection_reason' => $reason,
            'reviewed_by_id' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $this->dossierStatus->syncFor($student);
    }
}
```

- [ ] **Step 3: Reset the submission flag and recompute when a new required type is added; recompute (no reset) on update**

Edit `app/Domain/Students/Http/Controllers/RequiredDocumentTypeController.php`. Add imports `use App\Domain\Students\Models\Student;`, `use App\Domain\Students\Services\DossierStatusService;`, inject the service, and update `store()`/`update()`:

```php
class RequiredDocumentTypeController extends Controller
{
    public function __construct(
        private readonly DossierStatusService $dossierStatus,
    ) {}

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

        Student::query()->each(function (Student $student) {
            $student->setDocumentSubmitted(false);
            $student->setDocumentsZipPath(null);
            $student->save();
            $this->dossierStatus->syncFor($student);
        });

        return back()->with('status', 'Pièce requise ajoutée.');
    }

    public function update(UpdateRequiredDocumentTypeRequest $request, RequiredDocumentType $requiredDocumentType): RedirectResponse
    {
        $requiredDocumentType->update($request->validated());

        Student::query()->each(fn (Student $student) => $this->dossierStatus->syncFor($student));

        return back()->with('status', 'Pièce requise mise à jour.');
    }
}
```

`Student::query()->each()` is automatically tenant-scoped by `BelongsToTenant` — this only touches students of the acting admin's own school.

- [ ] **Step 4: Write the feature test**

Create `tests/Feature/Students/DossierStatusAutoComputeTest.php`:

```php
<?php

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Enums\DossierStatus;
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
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
    $this->type = RequiredDocumentType::factory()->create(['structure_id' => $this->structure->id]);
    $this->student = Student::factory()->stage(LifecycleStage::DossierSetup)->create(['structure_id' => $this->structure->id]);
});

it('moves a dossier from incomplete to complete when an admin uploads the last required document', function () {
    expect($this->student->dossier_status)->toBe(DossierStatus::Incomplete);

    $this->actingAs($this->admin)->post(route('students.documents.store', $this->student), [
        'required_document_type_id' => $this->type->id,
        'file' => UploadedFile::fake()->create('id.pdf', 10),
    ]);

    expect($this->student->fresh()->dossier_status)->toBe(DossierStatus::Complete);
});

it('moves a dossier from complete to validated when the last pending document is approved', function () {
    $this->actingAs($this->admin)->post(route('students.documents.store', $this->student), [
        'required_document_type_id' => $this->type->id,
        'file' => UploadedFile::fake()->create('id.pdf', 10),
    ]);
    expect($this->student->fresh()->dossier_status)->toBe(DossierStatus::Complete);

    $document = Document::query()->where('required_document_type_id', $this->type->id)->firstOrFail();
    $this->actingAs($this->admin)->post(route('documents.approve', $document));

    expect($this->student->fresh()->dossier_status)->toBe(DossierStatus::Validated);
});

it('drops a validated dossier back to complete when its document is rejected', function () {
    $this->actingAs($this->admin)->post(route('students.documents.store', $this->student), [
        'required_document_type_id' => $this->type->id,
        'file' => UploadedFile::fake()->create('id.pdf', 10),
    ]);
    $document = Document::query()->where('required_document_type_id', $this->type->id)->firstOrFail();
    $this->actingAs($this->admin)->post(route('documents.approve', $document));
    expect($this->student->fresh()->dossier_status)->toBe(DossierStatus::Validated);

    $this->actingAs($this->admin)->post(route('documents.reject', $document), ['reason' => 'Illisible']);

    expect($this->student->fresh()->dossier_status)->toBe(DossierStatus::Complete);
});

it('resets document_submitted and drops every tenant student back to incomplete when a new required type is added', function () {
    $this->student->setDocumentSubmitted(true);
    $this->student->setDocumentsZipPath('dossiers/old.zip');
    $this->student->save();
    (new App\Domain\Students\Services\DossierStatusService)->syncFor($this->student);
    expect($this->student->fresh()->dossier_status)->toBe(DossierStatus::Submitted);

    $this->actingAs($this->admin)->post(route('settings.document-types.store'), [
        'label' => 'Nouvelle pièce',
    ]);

    $this->student->refresh();
    expect($this->student->document_submitted)->toBeFalse();
    expect($this->student->documents_zip_path)->toBeNull();
    expect($this->student->dossier_status)->toBe(DossierStatus::Incomplete);
});

it('does not reset document_submitted when an existing required type is merely updated', function () {
    $this->student->setDocumentSubmitted(true);
    $this->student->save();
    (new App\Domain\Students\Services\DossierStatusService)->syncFor($this->student);

    $this->actingAs($this->admin)->patch(route('settings.document-types.update', $this->type), [
        'label' => 'Renommée',
        'is_active' => true,
    ]);

    expect($this->student->fresh()->document_submitted)->toBeTrue();
});
```

Check the actual route names for `settings.document-types.*` (`store`/`update`) via `php artisan route:list --name=document-types` before finalizing the test — use whatever names the existing routes actually have; do not guess.

- [ ] **Step 5: Run the tests**

Run: `php artisan test --compact tests/Feature/Students/DossierStatusAutoComputeTest.php`
Expected: 5 passed.

Run: `php artisan test --compact tests/Feature/Students tests/Feature/Documents tests/Unit/Students` (confirms the DocumentService/DocumentReviewController changes didn't break existing coverage — in particular `StudentDossierTest.php`'s upload/approve/reject tests, which now also trigger `syncFor()`)
Expected: all passed.

- [ ] **Step 6: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Documents/Services/DocumentService.php \
        app/Domain/Documents/Http/Controllers/DocumentReviewController.php \
        app/Domain/Students/Http/Controllers/RequiredDocumentTypeController.php \
        tests/Feature/Students/DossierStatusAutoComputeTest.php
git commit -m "feat(students,documents): auto-recompute dossier_status on document/type changes"
```

---

### Task 4: Bundle documents into a ZIP on submission, admin download, UI

**Files:**
- Create: `app/Domain/Students/Services/DocumentBundleService.php`
- Modify: `app/Domain/Students/Http/Controllers/StudentDossierController.php`
- Modify: `app/Domain/Students/Http/Controllers/StudentController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/students/show.blade.php`
- Test: `tests/Feature/Students/DossierSubmissionBundleTest.php` (new)

**Interfaces:**
- Consumes: `App\Domain\Students\Services\DossierStatusService::syncFor()` (Task 2).
- Produces: `DocumentBundleService::bundle(Student $student): string` — returns the disk-relative ZIP path, used by `StudentDossierController::submit()` and the new download route.

- [ ] **Step 1: Write `DocumentBundleService`**

Create `app/Domain/Students/Services/DocumentBundleService.php`:

```php
<?php

namespace App\Domain\Students\Services;

use App\Domain\Documents\Models\Document;
use App\Domain\Students\Models\Student;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * Bundles every current required-piece document into a single downloadable
 * ZIP archive, named {structure_id}_{student_id}_documents.zip - unique per
 * student (a literal "{structure_id}_eleve_documents" name, as originally
 * described, would collide across every student of the same school).
 * Called once, from StudentDossierController::submit().
 */
class DocumentBundleService
{
    public function bundle(Student $student): string
    {
        $documents = Document::query()
            ->where('documentable_type', $student->getMorphClass())
            ->where('documentable_id', $student->id)
            ->where('is_current', true)
            ->whereNotNull('required_document_type_id')
            ->with('requiredDocumentType')
            ->get();

        Storage::disk('local')->makeDirectory('dossiers');

        $relativePath = "dossiers/{$student->structure_id}_{$student->id}_documents.zip";
        $absolutePath = Storage::disk('local')->path($relativePath);

        $zip = new ZipArchive;
        $zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($documents as $document) {
            $sourcePath = Storage::disk($document->disk)->path($document->path);
            $entryName = ($document->requiredDocumentType?->label ?? $document->type->label()).'_'.$document->original_name;
            $zip->addFile($sourcePath, $entryName);
        }

        $zip->close();

        return $relativePath;
    }
}
```

- [ ] **Step 2: Wire bundling + flag into `submit()`**

Edit `app/Domain/Students/Http/Controllers/StudentDossierController.php`. Add imports `use App\Domain\Students\Services\DocumentBundleService;` and `use App\Domain\Students\Services\DossierStatusService;`, add both to the constructor, and extend `submit()` — after the existing approval check passes, before/around the existing lifecycle transitions:

```php
class StudentDossierController extends Controller
{
    public function __construct(
        private readonly DocumentService $documents,
        private readonly LifecycleService $lifecycle,
        private readonly DocumentBundleService $bundle,
        private readonly DossierStatusService $dossierStatus,
    ) {}

    // ...show(), upload() unchanged...

    public function submit(): RedirectResponse
    {
        $student = $this->currentStudent();

        $types = RequiredDocumentType::query()->active()->get();

        if ($types->isEmpty()) {
            return back()->withErrors(['dossier' => 'Aucune pièce requise n\'est définie.']);
        }

        $approvedDocs = Document::query()
            ->where('documentable_type', $student->getMorphClass())
            ->where('documentable_id', $student->id)
            ->where('is_current', true)
            ->where('review_status', DocumentReviewStatus::Approved)
            ->whereNotNull('required_document_type_id')
            ->pluck('required_document_type_id')
            ->all();

        $missingOrNotApproved = $types->reject(fn ($type) => in_array($type->id, $approvedDocs));

        if ($missingOrNotApproved->isNotEmpty()) {
            return back()->withErrors([
                'dossier' => 'Toutes les pièces doivent être validées avant la soumission du dossier. Veuillez vérifier les pièces en attente ou rejetées.',
            ]);
        }

        $zipPath = $this->bundle->bundle($student);
        $student->setDocumentSubmitted(true);
        $student->setDocumentsZipPath($zipPath);
        $student->save();
        $this->dossierStatus->syncFor($student);

        try {
            $this->lifecycle->transitionTo($student, LifecycleStage::Validation);
            $this->lifecycle->transitionTo($student, LifecycleStage::Enrollment);
        } catch (InvalidStageTransition) {
            return back()->withErrors(['dossier' => 'Votre dossier n\'est pas dans un état permettant la soumission.']);
        }

        return redirect()->route('eleve.dossier.show')->with('status', 'Dossier validé, votre inscription est confirmée.');
    }

    private function currentStudent(): Student
    {
        return Student::query()->where('user_id', Auth::id())->firstOrFail();
    }
}
```

(Every other method in the file is unchanged — only the constructor and `submit()`'s body grow.)

- [ ] **Step 3: Add the admin download route and controller action**

Edit `app/Domain/Students/Http/Controllers/StudentController.php`. Add a new method, placed after `updateDossierStatus()` was removed — put this one right before `createAccount()`:

```php
public function downloadDossier(Student $student): StreamedResponse
{
    $this->authorize('view', $student);

    abort_unless($student->documents_zip_path, 404);

    return Storage::disk('local')->download(
        $student->documents_zip_path,
        "dossier-{$student->fullName()}.zip"
    );
}
```

Add the imports `use Illuminate\Support\Facades\Storage;` and `use Symfony\Component\HttpFoundation\StreamedResponse;` to this controller.

Edit `routes/web.php`. In the same `role:admin|moniteur` group as `students.stage`/`students.create-account`, add:

```php
Route::get('students/{student}/dossier/download', [StudentController::class, 'downloadDossier'])->name('students.dossier-download');
```

- [ ] **Step 4: Add the download link to the student profile**

Edit `resources/views/students/show.blade.php`. In the dossier status `<x-card>` (from Task 2 Step 6), add a download link when a bundle exists:

```blade
<x-card>
    <div class="flex items-center justify-between mb-3">
        <div class="text-sm font-semibold text-content">Dossier administratif</div>
        <x-badge variant="info">{{ $student->dossier_status->label() }}</x-badge>
    </div>

    <ol class="flex flex-wrap gap-1.5">
        @foreach (\App\Domain\Students\Enums\DossierStatus::cases() as $status)
            <li @class([
                'px-2.5 py-1 rounded-ui-md text-xs font-medium',
                'bg-primary text-primary-content' => $status === $student->dossier_status,
                'bg-surface-inset text-content-secondary' => $status !== $student->dossier_status,
            ])>
                {{ $status->label() }}
            </li>
        @endforeach
    </ol>

    @if ($student->documents_zip_path)
        <div class="mt-4 pt-4 border-t border-border/60">
            <a href="{{ route('students.dossier-download', $student) }}" class="inline-flex items-center gap-1 text-sm text-primary hover:underline">
                <x-icon name="archive-box" class="w-4 h-4" /> Télécharger le dossier (ZIP)
            </a>
        </div>
    @endif
</x-card>
```

- [ ] **Step 5: Write the feature test**

Create `tests/Feature/Students/DossierSubmissionBundleTest.php`:

```php
<?php

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Enums\DossierStatus;
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
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
    $this->eleve = User::factory()->create(['structure_id' => $this->structure->id, 'email_verified_at' => now()]);
    $this->eleve->assignRole('eleve');
    $this->student = Student::factory()->stage(LifecycleStage::DossierSetup)->create([
        'structure_id' => $this->structure->id,
        'user_id' => $this->eleve->id,
    ]);
    $this->type = RequiredDocumentType::factory()->create(['structure_id' => $this->structure->id]);
});

it('bundles documents into a zip and flips document_submitted on successful submission', function () {
    $this->actingAs($this->eleve)->post(route('eleve.dossier.upload', $this->type), [
        'file' => UploadedFile::fake()->create('id.pdf', 10),
    ]);
    Document::query()->where('required_document_type_id', $this->type->id)->firstOrFail()
        ->update(['review_status' => DocumentReviewStatus::Approved]);

    $this->actingAs($this->eleve)->post(route('eleve.dossier.submit'))->assertRedirect(route('eleve.dossier.show'));

    $this->student->refresh();
    expect($this->student->document_submitted)->toBeTrue();
    expect($this->student->documents_zip_path)->not->toBeNull();
    expect($this->student->dossier_status)->toBe(DossierStatus::Submitted);
    Storage::disk('local')->assertExists($this->student->documents_zip_path);
});

it('does not bundle or flip document_submitted when submission is blocked', function () {
    $this->actingAs($this->eleve)->post(route('eleve.dossier.submit'))->assertSessionHasErrors('dossier');

    expect($this->student->fresh()->document_submitted)->toBeFalse();
    expect($this->student->fresh()->documents_zip_path)->toBeNull();
});

it('lets an admin download the submitted dossier bundle', function () {
    $this->actingAs($this->eleve)->post(route('eleve.dossier.upload', $this->type), [
        'file' => UploadedFile::fake()->create('id.pdf', 10),
    ]);
    Document::query()->where('required_document_type_id', $this->type->id)->firstOrFail()
        ->update(['review_status' => DocumentReviewStatus::Approved]);
    $this->actingAs($this->eleve)->post(route('eleve.dossier.submit'));

    $this->actingAs($this->admin)
        ->get(route('students.dossier-download', $this->student))
        ->assertOk();
});

it('404s the download route when no bundle exists yet', function () {
    $this->actingAs($this->admin)
        ->get(route('students.dossier-download', $this->student))
        ->assertNotFound();
});

it('does not let an admin of another tenant download the bundle', function () {
    $this->actingAs($this->eleve)->post(route('eleve.dossier.upload', $this->type), [
        'file' => UploadedFile::fake()->create('id.pdf', 10),
    ]);
    Document::query()->where('required_document_type_id', $this->type->id)->firstOrFail()
        ->update(['review_status' => DocumentReviewStatus::Approved]);
    $this->actingAs($this->eleve)->post(route('eleve.dossier.submit'));

    $otherStructure = Structure::factory()->create();
    $otherAdmin = User::factory()->create(['structure_id' => $otherStructure->id]);
    $otherAdmin->assignRole('admin');

    $this->actingAs($otherAdmin)
        ->get(route('students.dossier-download', $this->student))
        ->assertNotFound();
});
```

- [ ] **Step 6: Run the tests**

Run: `php artisan test --compact tests/Feature/Students/DossierSubmissionBundleTest.php`
Expected: 5 passed.

Run: `php artisan test --compact tests/Feature/Students` (full regression on this domain, including the pre-existing `StudentDossierTest.php` and `DossierEndToEndTest.php` which exercise the same `submit()` action this task modified)
Expected: all passed.

- [ ] **Step 7: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Students/Services/DocumentBundleService.php \
        app/Domain/Students/Http/Controllers/StudentDossierController.php \
        app/Domain/Students/Http/Controllers/StudentController.php \
        routes/web.php \
        resources/views/students/show.blade.php \
        tests/Feature/Students/DossierSubmissionBundleTest.php
git commit -m "feat(students): bundle dossier documents into a downloadable zip on submission"
```

---

### Task 5: Whole-branch verification

**Files:** none (verification only).

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: all passed, including `tests/Architecture/DomainBoundariesTest.php` — confirm the new `DocumentService`/`DocumentReviewController` → `DossierStatusService` dependency doesn't trip any rule (it shouldn't: Documents → Students is explicitly allowed).

- [ ] **Step 2: Manually confirm the end-to-end flow**

Using Tinker or the browser: create a student, add a required document type, upload a document as that student (status should become Complete), approve it as admin (status should become Validated), submit as the student (status should become Submitted, a ZIP should exist and be downloadable by the admin from the student profile), then add a new required document type as admin and confirm the student's `document_submitted` resets to false and status drops back to Incomplete.
