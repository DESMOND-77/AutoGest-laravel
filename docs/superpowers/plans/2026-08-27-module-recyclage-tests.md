# Module Recyclage & Tests Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a new `Recyclage` domain letting an admin record a one-off, billable "Recyclage" or "Test" session for a person who is not an enrolled `Student` (name, phone, assigned moniteur, session date, amount), and have every entry automatically post to the Finance ledger as income — via the exact decoupled event/listener pattern already used for Fleet→Finance (`VehicleExpenseRecorded` → `RecordVehicleExpenseInLedger`), never a direct call from `Recyclage` into `Finance`.

**Architecture:** `App\Domain\Recyclage` is a new, small leaf domain, structurally modeled on `App\Domain\Fleet` (named in the prompt as the closest reference in size/complexity). `RecyclageEntry` never references `Student` — it is a standalone contact record, not a lifecycle object. Creation dispatches `RecyclageEntryRecorded`; a top-level `app/Listeners/RecordRecyclageEntryInLedger` (auto-discovered by Laravel's event system, exactly like `RecordVehicleExpenseInLedger`) turns that into a `LedgerEntry` via the existing `LedgerService::recordManual()` — `Recyclage` never imports anything from `Finance`, and the listener lives in neither domain.

**Tech Stack:** Laravel 12, PHP 8.5, Pest 3, Blade, Spatie laravel-permission.

**Spec:** `Promptset/09-module-recyclage-tests.md`. This prompt carried an explicit blocking business-confirmation gate ("ne pas implémenter avant confirmation explicite du métier gabonais") — confirmation was obtained from the user directly before this plan was written; do not re-ask.

## Global Constraints

- Domain name: `App\Domain\Recyclage` (kept as the specific French business term, same reasoning the codebase already applies to `CRM` — a domain-specific proper term, not translated).
- `RecyclageEntry` must NEVER have a foreign key or relation to `Student`, even though the same person may separately be a former student. No `student_id` column, no `Student` import anywhere in this domain.
- The Finance integration is exclusively via `RecyclageEntryRecorded` (event) → `app/Listeners/RecordRecyclageEntryInLedger` (top-level listener, outside both domains) → `LedgerService::recordManual()`. `App\Domain\Recyclage` must never import anything from `App\Domain\Finance`, and vice versa.
- `BelongsToTenant` on `RecyclageEntry`, exactly like every other tenant-scoped model — no manual `structure_id` filtering anywhere (rely on the ambient `TenantContext` scope, per this session's established convention: do not add explicit `structure_id` filters where the ambient scope already applies).
- Route group `role:admin` only, prefix/name `recyclage.*`.
- No arrow glyphs (←/→) in any UI copy.
- Every change must have a passing Pest test. Run `vendor/bin/pint --dirty --format agent` after PHP edits.

---

### Task 1: Migration, model, enum, factory

**Files:**
- Create: migration via `php artisan make:migration create_recyclage_entries_table`
- Create: `app/Domain/Recyclage/Models/RecyclageEntry.php`
- Create: `app/Domain/Recyclage/Enums/RecyclageMotif.php`
- Create: `app/Domain/Recyclage/Database/Factories/RecyclageEntryFactory.php`
- Test: `tests/Unit/Recyclage/RecyclageEntryTest.php` (new)

**Interfaces:**
- Produces: `RecyclageEntry` model (fillable: `structure_id`, `full_name`, `motif`, `phone`, `instructor_id`, `session_date`, `amount`; casts: `motif` → `RecyclageMotif`, `session_date` → `date`, `amount` → `decimal:2`), `RecyclageMotif` enum (`Test`, `Recyclage`, both with `label()`), `RecyclageEntryFactory`. Consumed by Task 2 (event dispatch) and Task 3 (controller/policy).

- [ ] **Step 1: Create the migration**

Run: `php artisan make:migration create_recyclage_entries_table --no-interaction`

Edit the generated file:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recyclage_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('structure_id')->constrained('structures')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('motif');
            $table->string('phone')->nullable();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('session_date');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recyclage_entries');
    }
};
```

- [ ] **Step 2: Write the failing model test**

Create `tests/Unit/Recyclage/RecyclageEntryTest.php`:

```php
<?php

use App\Domain\Recyclage\Enums\RecyclageMotif;
use App\Domain\Recyclage\Models\RecyclageEntry;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use App\Support\TenantContext;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    TenantContext::set($this->structure);
});

afterEach(fn () => TenantContext::clear());

it('creates a recyclage entry scoped to the current tenant', function () {
    $instructor = User::factory()->create(['structure_id' => $this->structure->id]);

    $entry = RecyclageEntry::query()->create([
        'full_name' => 'Jean Mabika',
        'motif' => RecyclageMotif::Recyclage->value,
        'phone' => '074000000',
        'instructor_id' => $instructor->id,
        'session_date' => now()->toDateString(),
        'amount' => 15000,
    ]);

    expect($entry->structure_id)->toBe($this->structure->id);
    expect($entry->motif)->toBe(RecyclageMotif::Recyclage);
    expect($entry->fresh()->amount)->toBe('15000.00');
    expect($entry->instructor->id)->toBe($instructor->id);
});

it('scopes queries to the current tenant only', function () {
    $otherStructure = Structure::factory()->create();

    RecyclageEntry::factory()->create(['structure_id' => $this->structure->id]);
    RecyclageEntry::factory()->create(['structure_id' => $otherStructure->id]);

    expect(RecyclageEntry::query()->count())->toBe(1);
});
```

- [ ] **Step 3: Run the test, verify it fails**

Run: `php artisan test --compact tests/Unit/Recyclage/RecyclageEntryTest.php`
Expected: FAIL (classes don't exist yet).

- [ ] **Step 4: Write the enum**

Create `app/Domain/Recyclage/Enums/RecyclageMotif.php`:

```php
<?php

namespace App\Domain\Recyclage\Enums;

enum RecyclageMotif: string
{
    case Test = 'test';
    case Recyclage = 'recyclage';

    public function label(): string
    {
        return match ($this) {
            self::Test => 'Test',
            self::Recyclage => 'Recyclage',
        };
    }
}
```

- [ ] **Step 5: Write the model**

Create `app/Domain/Recyclage/Models/RecyclageEntry.php`:

```php
<?php

namespace App\Domain\Recyclage\Models;

use App\Domain\Recyclage\Database\Factories\RecyclageEntryFactory;
use App\Domain\Recyclage\Enums\RecyclageMotif;
use App\Models\User;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A one-off, billable session for someone who is NOT an enrolled Student -
 * deliberately has no relation to App\Domain\Students\Models\Student, even
 * when the same person happens to be a former student elsewhere.
 */
class RecyclageEntry extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return RecyclageEntryFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'full_name',
        'motif',
        'phone',
        'instructor_id',
        'session_date',
        'amount',
    ];

    protected $casts = [
        'motif' => RecyclageMotif::class,
        'session_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
```

- [ ] **Step 6: Write the factory**

Create `app/Domain/Recyclage/Database/Factories/RecyclageEntryFactory.php`:

```php
<?php

namespace App\Domain\Recyclage\Database\Factories;

use App\Domain\Recyclage\Enums\RecyclageMotif;
use App\Domain\Recyclage\Models\RecyclageEntry;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecyclageEntry>
 */
class RecyclageEntryFactory extends Factory
{
    protected $model = RecyclageEntry::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'full_name' => $this->faker->name(),
            'motif' => RecyclageMotif::Test,
            'phone' => $this->faker->phoneNumber(),
            'session_date' => now()->toDateString(),
            'amount' => 15000,
        ];
    }
}
```

- [ ] **Step 7: Run the test, verify it passes**

Run: `php artisan test --compact tests/Unit/Recyclage/RecyclageEntryTest.php`
Expected: 2 passed.

- [ ] **Step 8: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add database/migrations app/Domain/Recyclage/Models/RecyclageEntry.php \
        app/Domain/Recyclage/Enums/RecyclageMotif.php \
        app/Domain/Recyclage/Database/Factories/RecyclageEntryFactory.php \
        tests/Unit/Recyclage/RecyclageEntryTest.php
git commit -m "feat(recyclage): add RecyclageEntry model, enum, migration and factory"
```

---

### Task 2: Event + decoupled ledger listener

**Files:**
- Create: `app/Domain/Recyclage/Events/RecyclageEntryRecorded.php`
- Create: `app/Listeners/RecordRecyclageEntryInLedger.php`
- Test: `tests/Feature/Recyclage/RecyclageLedgerIntegrationTest.php` (new)
- Modify: `tests/Architecture/DomainBoundariesTest.php`

**Interfaces:**
- Produces: `RecyclageEntryRecorded` event (constructor: `RecyclageEntry $entry, float $amount, string $fullName, string $sessionDate`), dispatched by Task 3's controller.
- Consumes: `App\Domain\Finance\Services\LedgerService::recordManual()` (existing, unchanged) — called only from the listener, never from `App\Domain\Recyclage`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Recyclage/RecyclageLedgerIntegrationTest.php`:

```php
<?php

use App\Domain\Finance\Models\LedgerEntry;
use App\Domain\Recyclage\Enums\RecyclageMotif;
use App\Domain\Recyclage\Events\RecyclageEntryRecorded;
use App\Domain\Recyclage\Models\RecyclageEntry;
use App\Domain\Tenancy\Models\Structure;
use App\Listeners\RecordRecyclageEntryInLedger;
use App\Support\TenantContext;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    TenantContext::set($this->structure);
});

afterEach(fn () => TenantContext::clear());

it('creates an income ledger entry when a recyclage entry is recorded', function () {
    $entry = RecyclageEntry::factory()->create([
        'structure_id' => $this->structure->id,
        'full_name' => 'Awa Tenant',
        'motif' => RecyclageMotif::Test,
        'session_date' => '2026-07-21',
        'amount' => 20000,
    ]);

    (new RecordRecyclageEntryInLedger(app(\App\Domain\Finance\Services\LedgerService::class)))
        ->handle(new RecyclageEntryRecorded($entry, 20000.0, 'Awa Tenant', '2026-07-21'));

    $ledgerEntry = LedgerEntry::query()->sole();
    expect($ledgerEntry->type->value)->toBe('income');
    expect((float) $ledgerEntry->amount)->toBe(20000.0);
    expect($ledgerEntry->occurred_on->toDateString())->toBe('2026-07-21');
    expect($ledgerEntry->memo)->toContain('Awa Tenant');
});
```

- [ ] **Step 2: Run the test, verify it fails**

Run: `php artisan test --compact tests/Feature/Recyclage/RecyclageLedgerIntegrationTest.php`
Expected: FAIL (classes don't exist yet).

- [ ] **Step 3: Write the event**

Create `app/Domain/Recyclage/Events/RecyclageEntryRecorded.php`:

```php
<?php

namespace App\Domain\Recyclage\Events;

use App\Domain\Recyclage\Models\RecyclageEntry;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Recyclage's entire contribution to bookkeeping - turning this into a
 * LedgerEntry is somebody else's job (see RecordRecyclageEntryInLedger),
 * exactly mirroring App\Domain\Fleet\Events\VehicleExpenseRecorded. Recyclage
 * must never depend on Finance directly.
 */
class RecyclageEntryRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly RecyclageEntry $entry,
        public readonly float $amount,
        public readonly string $fullName,
        public readonly string $sessionDate,
    ) {}
}
```

- [ ] **Step 4: Write the listener**

Create `app/Listeners/RecordRecyclageEntryInLedger.php`:

```php
<?php

namespace App\Listeners;

use App\Domain\Finance\Enums\LedgerEntryType;
use App\Domain\Finance\Services\LedgerService;
use App\Domain\Recyclage\Events\RecyclageEntryRecorded;

/**
 * The integration point between Recyclage and Finance - deliberately
 * outside both app/Domain/Recyclage and app/Domain/Finance, so neither
 * domain has to know the other exists. Mirrors RecordVehicleExpenseInLedger
 * exactly (see FIN-04 in docs/audit/business-workflow.md).
 */
class RecordRecyclageEntryInLedger
{
    public function __construct(
        private readonly LedgerService $ledger,
    ) {}

    public function handle(RecyclageEntryRecorded $event): void
    {
        $this->ledger->recordManual([
            'type' => LedgerEntryType::Income->value,
            'amount' => $event->amount,
            'memo' => "Recyclage/Test - {$event->fullName}",
            'occurred_on' => $event->sessionDate,
        ]);
    }
}
```

This listener lives in `app/Listeners/` (top-level), which this codebase's event auto-discovery already picks up by its `handle(RecyclageEntryRecorded)` signature — no manual `Event::listen()` registration needed, exactly like `RecordVehicleExpenseInLedger`. Do not add one to `AppServiceProvider`.

- [ ] **Step 5: Add architecture rules**

Edit `tests/Architecture/DomainBoundariesTest.php`. Add a new rule, placed near the other single-domain exclusion rules (e.g. near the Fleet/Students rules):

```php
arch('Recyclage domain does not depend on Finance or Students')
    ->expect('App\Domain\Recyclage')
    ->not->toUse([
        'App\Domain\Finance',
        'App\Domain\Students',
    ]);
```

- [ ] **Step 6: Run the tests, verify they pass**

Run: `php artisan test --compact tests/Feature/Recyclage/RecyclageLedgerIntegrationTest.php`
Expected: 1 passed.

Run: `php artisan test --compact tests/Architecture`
Expected: all passed (including the new rule).

- [ ] **Step 7: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Recyclage/Events/RecyclageEntryRecorded.php \
        app/Listeners/RecordRecyclageEntryInLedger.php \
        tests/Feature/Recyclage/RecyclageLedgerIntegrationTest.php \
        tests/Architecture/DomainBoundariesTest.php
git commit -m "feat(recyclage): post recyclage entries to the Finance ledger via a decoupled listener"
```

---

### Task 3: Policy, form request, controller, routes

**Files:**
- Create: `app/Domain/Recyclage/Policies/RecyclageEntryPolicy.php`
- Create: `app/Domain/Recyclage/Http/Requests/StoreRecyclageEntryRequest.php`
- Create: `app/Domain/Recyclage/Http/Controllers/RecyclageController.php`
- Modify: `routes/web.php`
- Modify: `app/Providers/AuthServiceProvider.php` (or wherever policies are registered — check first)
- Test: `tests/Feature/Recyclage/RecyclageControllerTest.php` (new)

**Interfaces:**
- Consumes: `RecyclageEntry`, `RecyclageMotif` (Task 1), `RecyclageEntryRecorded` (Task 2).
- Produces: routes `recyclage.index` (GET), `recyclage.store` (POST), `recyclage.destroy` (DELETE) — consumed by Task 4's view/nav.

- [ ] **Step 1: Check how policies are registered in this app**

Run: `grep -n "Policy::class\|registerPolicies\|Gate::policy" app/Providers/AuthServiceProvider.php app/Providers/AppServiceProvider.php`

Laravel 12 auto-discovers policies by naming convention (`Model` → `ModelPolicy` in a `Policies` subdirectory of the same domain) in most setups — confirm whether this project relies on that auto-discovery (check if `VehiclePolicy` is manually registered anywhere) or requires an explicit `Gate::policy()` call, and follow whichever this project already does for `Vehicle`/`VehiclePolicy`.

- [ ] **Step 2: Write the failing tests**

Create `tests/Feature/Recyclage/RecyclageControllerTest.php`:

```php
<?php

use App\Domain\Finance\Models\LedgerEntry;
use App\Domain\Recyclage\Enums\RecyclageMotif;
use App\Domain\Recyclage\Models\RecyclageEntry;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
});

it('lets an admin record a recyclage entry that immediately posts to the ledger', function () {
    $instructor = User::factory()->create(['structure_id' => $this->structure->id]);
    $instructor->assignRole('moniteur');

    $response = $this->actingAs($this->admin)->post(route('recyclage.store'), [
        'full_name' => 'Jean Mabika',
        'motif' => RecyclageMotif::Recyclage->value,
        'phone' => '074000000',
        'instructor_id' => $instructor->id,
        'session_date' => now()->toDateString(),
        'amount' => 15000,
    ]);

    $response->assertRedirect(route('recyclage.index'));

    $entry = RecyclageEntry::query()->where('full_name', 'Jean Mabika')->sole();
    expect($entry->structure_id)->toBe($this->structure->id);

    $ledgerEntry = LedgerEntry::query()->sole();
    expect((float) $ledgerEntry->amount)->toBe(15000.0);
    expect($ledgerEntry->type->value)->toBe('income');
});

it('validates the required fields', function () {
    $this->actingAs($this->admin)->post(route('recyclage.store'), [])
        ->assertSessionHasErrors(['full_name', 'motif', 'session_date', 'amount']);

    expect(RecyclageEntry::query()->count())->toBe(0);
});

it('scopes the index list to the current tenant', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    RecyclageEntry::factory()->create(['structure_id' => $this->structure->id, 'full_name' => 'Awa Tenant']);
    RecyclageEntry::factory()->create(['structure_id' => $otherStructure->id, 'full_name' => 'Autre Ecole']);

    $this->actingAs($this->admin)->get(route('recyclage.index'))
        ->assertOk()
        ->assertSee('Awa Tenant')
        ->assertDontSee('Autre Ecole');
});

it('denies a moniteur access to recyclage routes entirely', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->get(route('recyclage.index'))->assertForbidden();
    $this->actingAs($moniteur)->post(route('recyclage.store'), [])->assertForbidden();
});

it('denies an eleve access to recyclage routes entirely', function () {
    $eleve = User::factory()->create(['structure_id' => $this->structure->id]);
    $eleve->assignRole('eleve');

    $this->actingAs($eleve)->get(route('recyclage.index'))->assertForbidden();
});

it('lets an admin delete a recyclage entry', function () {
    $entry = RecyclageEntry::factory()->create(['structure_id' => $this->structure->id]);

    $this->actingAs($this->admin)->delete(route('recyclage.destroy', $entry))
        ->assertRedirect(route('recyclage.index'));

    expect(RecyclageEntry::query()->count())->toBe(0);
});

it('does not let an admin delete another tenant\'s recyclage entry', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $entry = RecyclageEntry::factory()->create(['structure_id' => $otherStructure->id]);

    $this->actingAs($this->admin)->delete(route('recyclage.destroy', $entry))
        ->assertNotFound();

    expect(RecyclageEntry::withoutGlobalScopes()->find($entry->id))->not->toBeNull();
});
```

- [ ] **Step 3: Run the tests, verify they fail**

Run: `php artisan test --compact tests/Feature/Recyclage/RecyclageControllerTest.php`
Expected: FAIL (route/policy/request/controller don't exist yet).

- [ ] **Step 4: Write the policy**

Create `app/Domain/Recyclage/Policies/RecyclageEntryPolicy.php`, mirroring `VehiclePolicy` (admin-only, no `update`):

```php
<?php

namespace App\Domain\Recyclage\Policies;

use App\Domain\Recyclage\Models\RecyclageEntry;
use App\Models\User;

class RecyclageEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, RecyclageEntry $entry): bool
    {
        return $user->hasRole('admin') && $entry->structure_id === $user->structure_id;
    }
}
```

If Step 1 found this project requires explicit policy registration (rather than relying on Laravel's naming-convention auto-discovery), register it the same way `VehiclePolicy` is registered.

- [ ] **Step 5: Write the form request**

Create `app/Domain/Recyclage/Http/Requests/StoreRecyclageEntryRequest.php`:

```php
<?php

namespace App\Domain\Recyclage\Http\Requests;

use App\Domain\Recyclage\Enums\RecyclageMotif;
use App\Domain\Recyclage\Models\RecyclageEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreRecyclageEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', RecyclageEntry::class);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'motif' => ['required', new Enum(RecyclageMotif::class)],
            'phone' => ['nullable', 'string', 'max:30'],
            'instructor_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('structure_id', $this->user()->structure_id),
            ],
            'session_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
```

- [ ] **Step 6: Write the controller**

Create `app/Domain/Recyclage/Http/Controllers/RecyclageController.php`:

```php
<?php

namespace App\Domain\Recyclage\Http\Controllers;

use App\Domain\Recyclage\Events\RecyclageEntryRecorded;
use App\Domain\Recyclage\Http\Requests\StoreRecyclageEntryRequest;
use App\Domain\Recyclage\Models\RecyclageEntry;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;

class RecyclageController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', RecyclageEntry::class);

        return view('recyclage.index', [
            'entries' => RecyclageEntry::query()->with('instructor')->latest('session_date')->paginate(20),
            'instructors' => User::role('moniteur')->active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreRecyclageEntryRequest $request): RedirectResponse
    {
        $entry = RecyclageEntry::query()->create($request->validated());

        Event::dispatch(new RecyclageEntryRecorded(
            $entry,
            (float) $entry->amount,
            $entry->full_name,
            $entry->session_date->toDateString(),
        ));

        return redirect()->route('recyclage.index')->with('status', 'Entrée enregistrée.');
    }

    public function destroy(RecyclageEntry $entry): RedirectResponse
    {
        $this->authorize('delete', $entry);

        $entry->delete();

        return redirect()->route('recyclage.index')->with('status', 'Entrée supprimée.');
    }
}
```

- [ ] **Step 7: Add the routes**

Edit `routes/web.php`. Add the import `use App\Domain\Recyclage\Http\Controllers\RecyclageController;` and a new `role:admin` route group (place it near the Fleet or Finance groups):

```php
Route::middleware(['auth', 'role:admin'])
    ->prefix('recyclage')
    ->name('recyclage.')
    ->group(function () {
        Route::get('/', [RecyclageController::class, 'index'])->name('index');
        Route::post('/', [RecyclageController::class, 'store'])->name('store');
        Route::delete('{entry}', [RecyclageController::class, 'destroy'])->name('destroy');
    });
```

- [ ] **Step 8: Run the tests**

Run: `php artisan test --compact tests/Feature/Recyclage/RecyclageControllerTest.php`
Expected: 7 passed.

- [ ] **Step 9: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Recyclage/Policies/RecyclageEntryPolicy.php \
        app/Domain/Recyclage/Http/Requests/StoreRecyclageEntryRequest.php \
        app/Domain/Recyclage/Http/Controllers/RecyclageController.php \
        routes/web.php \
        tests/Feature/Recyclage/RecyclageControllerTest.php
git commit -m "feat(recyclage): add policy, form request, controller and routes"
```

(If Step 1 required a policy-registration file change, include it in this commit too.)

---

### Task 4: View and navigation

**Files:**
- Create: `resources/views/recyclage/index.blade.php`
- Modify: `resources/views/layouts/partials/sidebar-nav.blade.php`
- Test: `tests/Feature/Recyclage/RecyclageControllerTest.php` (extend, if needed for nav visibility — see Step 3)

**Interfaces:**
- Consumes: `entries` (paginated `RecyclageEntry`), `instructors` (Task 3's controller).

- [ ] **Step 1: Write the view**

Create `resources/views/recyclage/index.blade.php`, modeled on `resources/views/finance/ledger/index.blade.php`'s list+create-form layout:

```blade
<x-app-layout>
    <x-slot name="header">Recyclage &amp; Tests</x-slot>

    <div class="py-6 space-y-5 max-w-4xl mx-auto">
        @if (session('status'))
            <x-alert variant="success">{{ session('status') }}</x-alert>
        @endif
        @if ($errors->any())
            <x-alert variant="danger">{{ $errors->first() }}</x-alert>
        @endif

        <x-card>
            <div class="text-sm font-semibold text-content mb-3">Nouvelle entrée</div>
            <form method="POST" action="{{ route('recyclage.store') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @csrf
                <div>
                    <x-input-label for="full_name" value="Nom complet" />
                    <x-text-input id="full_name" name="full_name" class="block mt-1 w-full" required />
                </div>
                <div>
                    <x-input-label for="motif" value="Motif" />
                    <select id="motif" name="motif" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        @foreach (\App\Domain\Recyclage\Enums\RecyclageMotif::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="phone" value="Téléphone" />
                    <x-text-input id="phone" name="phone" class="block mt-1 w-full" />
                </div>
                <div>
                    <x-input-label for="instructor_id" value="Moniteur" />
                    <select id="instructor_id" name="instructor_id" class="mt-1 w-full rounded-ui-md border-0 bg-surface-inset text-content shadow-inset focus:shadow-inset-focus focus:ring-0 text-sm">
                        <option value="">— Aucun —</option>
                        @foreach ($instructors as $instructor)
                            <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="session_date" value="Date de séance" />
                    <x-text-input id="session_date" type="date" name="session_date" class="block mt-1 w-full" required />
                </div>
                <div>
                    <x-input-label for="amount" value="Montant" />
                    <x-text-input id="amount" type="number" step="0.01" name="amount" class="block mt-1 w-full" required />
                </div>
                <div class="sm:col-span-3 flex justify-end">
                    <x-primary-button>Enregistrer</x-primary-button>
                </div>
            </form>
        </x-card>

        <x-card :padded="false">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-content-muted">
                        <tr>
                            <th class="px-5 py-3 font-medium">Nom</th>
                            <th class="px-5 py-3 font-medium">Motif</th>
                            <th class="px-5 py-3 font-medium">Moniteur</th>
                            <th class="px-5 py-3 font-medium">Date</th>
                            <th class="px-5 py-3 font-medium">Montant</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        @forelse ($entries as $entry)
                            <tr class="hover:bg-surface-elevated/60 transition">
                                <td class="px-5 py-3 text-content font-medium">{{ $entry->full_name }}</td>
                                <td class="px-5 py-3"><x-badge variant="info">{{ $entry->motif->label() }}</x-badge></td>
                                <td class="px-5 py-3 text-content-secondary">{{ $entry->instructor?->name ?? '-' }}</td>
                                <td class="px-5 py-3 text-content-secondary">{{ $entry->session_date->format('d/m/Y') }}</td>
                                <td class="px-5 py-3 text-content">{{ number_format($entry->amount, 0, ',', ' ') }} FCFA</td>
                                <td class="px-5 py-3 text-right">
                                    <form method="POST" action="{{ route('recyclage.destroy', $entry) }}" onsubmit="return confirm('Supprimer cette entrée ?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-danger hover:underline">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <x-empty-table-row
                                colspan="6"
                                title="Aucune entrée."
                                message="Enregistrez une prestation de recyclage ou un test isolé."
                            />
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3 border-t border-border/60">
                {{ $entries->links() }}
            </div>
        </x-card>
    </div>
</x-app-layout>
```

- [ ] **Step 2: Add the sidebar navigation entry**

Edit `resources/views/layouts/partials/sidebar-nav.blade.php`. In the `@can('viewAny', \App\Domain\Students\Models\Student::class)` "Gestion" block (the one already containing Élèves/Prospects/Dossiers/Moniteurs), add, inside the `@if ($user?->hasRole('admin'))` sub-block, right after the "Dossiers en attente" link:

```blade
<x-sidebar-link :href="route('recyclage.index')" :active="request()->routeIs('recyclage.*')" icon="clock">Recyclage &amp; Tests</x-sidebar-link>
```

- [ ] **Step 3: Add a feature test asserting the view renders and the nav link appears for an admin only**

Append to `tests/Feature/Recyclage/RecyclageControllerTest.php`:

```php
it('shows the recyclage link in the sidebar for an admin but not for a moniteur', function () {
    $this->actingAs($this->admin)->get(route('recyclage.index'))
        ->assertOk()
        ->assertSee('Recyclage &amp; Tests', false);

    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->get(route('moniteur.dashboard'))
        ->assertOk()
        ->assertDontSee('Recyclage &amp; Tests', false);
});
```

- [ ] **Step 4: Run the tests**

Run: `php artisan test --compact tests/Feature/Recyclage`
Expected: 8 passed.

- [ ] **Step 5: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add resources/views/recyclage/index.blade.php \
        resources/views/layouts/partials/sidebar-nav.blade.php \
        tests/Feature/Recyclage/RecyclageControllerTest.php
git commit -m "feat(recyclage): add the recyclage screen and sidebar navigation entry"
```

---

### Task 5: Whole-branch verification

**Files:** none (verification only).

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: all passed, including `tests/Architecture/DomainBoundariesTest.php` — confirm the new `Recyclage` domain rule passes and no other rule was accidentally tripped (e.g. `App\Listeners\RecordRecyclageEntryInLedger` living outside both domains must not itself trip any "domain does not depend on X" rule, since it's not inside `App\Domain\*`).

- [ ] **Step 2: Manually confirm the acceptance criteria**

Using the browser or Tinker: as an admin, record a Recyclage entry, confirm it immediately appears on the ledger screen (`finance.ledger.index`) as an income entry with the correct amount and a memo naming the person; confirm a moniteur/eleve get 403 on every `recyclage.*` route; confirm `App\Domain\Recyclage` has zero references to `App\Domain\Finance` or `App\Domain\Students` anywhere in its source (`grep -rl "Domain\\\\Finance\|Domain\\\\Students" app/Domain/Recyclage` should return nothing).
