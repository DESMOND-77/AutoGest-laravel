# Feuille de Route Moniteur Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a per-student "feuille de route" screen for a moniteur: session totals/presence/absence counts, completed practical driving hours, a detailed session history, and a skill-progress summary (`x/y acquises - z%`) linking to the existing evaluation screen — scoped strictly to sessions the viewing moniteur themselves conducted with that student.

**Architecture:** Two small aggregation additions, no new columns (session duration is computed from `starts_at`/`ends_at` at read time, per the prompt's explicit anti-duplication constraint): a new `StudentSessionSummaryService` in the Scheduling domain aggregates `LessonSession` rows for a (student, instructor) pair, and a new `EvaluationService::acquiredSummary()` method (Training domain, alongside the existing `record()`) computes the `x/y acquises - z%` figure so the new screen doesn't re-derive the skill/progress join from scratch — the actual reusable unit here is "count of acquired skills for a student," which the evaluation and eleve-progression screens already each compute inline; this extracts it once. Access is gated by the *existing* `StudentPolicy::view()` (already correctly restricts a moniteur to only their assigned students — verified in the Students domain, no new policy needed), matching the exact pattern `students.show` already uses (an unconditional link in the list, the destination route enforces the real check).

**Tech Stack:** Laravel 12, PHP 8.5, Pest 3, Blade.

**Spec:** `Promptset/05-feuille-route-moniteur.md`, with two points confirmed against current code before writing this plan (both noted inline below since they affect scope):
- `StudentController::index()`/`EloquentStudentRepository::paginate()` do **not** filter the student list by moniteur assignment today (a moniteur sees every student in the tenant in the list) — only `StudentPolicy::view()` (single-student access) restricts by assignment. This plan does not change list-filtering behavior (out of scope, not requested); it reuses the existing `view()` gate for the new route, exactly like `students.show` already does.
- The prompt says to reuse "the skill-progress calculation already used by `training.evaluation.show`" — that screen (and the eleve `Ma Progression` screen) currently compute **per-category** subtotals with no single overall `x/y - z%` figure anywhere yet. This plan adds that one new aggregate method rather than pretending an identical calculation already exists to import — but places it in the same service (`EvaluationService`) so a future caller has one place to get it, not three.

## Global Constraints

- No new `LessonSession` column for duration — always compute from `starts_at`/`ends_at` (`time` columns) via `Carbon::parse($ends_at)->diffInMinutes(Carbon::parse($starts_at))` at read time.
- The new route/controller action must scope `LessonSession` rows to `instructor_id === Auth::id()` AND `student_id === $student->id` — a moniteur's "feuille de route" for a student only reflects sessions *they themselves* conducted, per the prompt's explicit "avec ce moniteur" wording, not every session anyone ever ran with that student.
- Access control: `$this->authorize('view', $student)` (existing `StudentPolicy::view()`), no new policy class.
- No arrow glyphs (←/→) in any UI copy.
- Every change must have a passing Pest test. Run `vendor/bin/pint --dirty --format agent` after PHP edits.

---

### Task 1: `StudentSessionSummaryService` — session aggregates for a (student, instructor) pair

**Files:**
- Create: `app/Domain/Scheduling/Services/StudentSessionSummaryService.php`
- Test: `tests/Unit/Scheduling/StudentSessionSummaryServiceTest.php` (new)

**Interfaces:**
- Produces: `StudentSessionSummaryService::summarize(Student $student, User $instructor): array` — returns `['total' => int, 'present' => int, 'absent' => int, 'practicalHoursCompleted' => float, 'sessions' => Collection<LessonSession>]`, consumed by Task 3's controller.

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Scheduling/StudentSessionSummaryServiceTest.php`:

```php
<?php

use App\Domain\Scheduling\Enums\PresenceStatus;
use App\Domain\Scheduling\Enums\SessionType;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Scheduling\Services\StudentSessionSummaryService;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    $this->instructor = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->otherInstructor = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->student = Student::factory()->create(['structure_id' => $this->structure->id]);
    $this->service = new StudentSessionSummaryService;
});

it('counts total, present, and absent sessions for the given instructor only', function () {
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id, 'presence' => PresenceStatus::Present,
        'type' => SessionType::Practical, 'starts_at' => '08:00', 'ends_at' => '09:00',
    ]);
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id, 'presence' => PresenceStatus::Absent,
        'type' => SessionType::Practical, 'starts_at' => '08:00', 'ends_at' => '09:30',
    ]);
    // Belongs to a different instructor - must not be counted.
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->otherInstructor->id, 'presence' => PresenceStatus::Present,
    ]);

    $summary = $this->service->summarize($this->student, $this->instructor);

    expect($summary['total'])->toBe(2);
    expect($summary['present'])->toBe(1);
    expect($summary['absent'])->toBe(1);
});

it('sums completed practical hours from present practical sessions only', function () {
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id, 'presence' => PresenceStatus::Present,
        'type' => SessionType::Practical, 'starts_at' => '08:00', 'ends_at' => '09:00',
    ]);
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id, 'presence' => PresenceStatus::Present,
        'type' => SessionType::Practical, 'starts_at' => '10:00', 'ends_at' => '11:30',
    ]);
    // Present but theoretical - must not count toward driving hours.
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id, 'presence' => PresenceStatus::Present,
        'type' => SessionType::Theoretical, 'starts_at' => '08:00', 'ends_at' => '10:00',
    ]);
    // Practical but not present - must not count.
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id, 'presence' => PresenceStatus::Planned,
        'type' => SessionType::Practical, 'starts_at' => '08:00', 'ends_at' => '09:00',
    ]);

    $summary = $this->service->summarize($this->student, $this->instructor);

    expect($summary['practicalHoursCompleted'])->toBe(2.5);
});

it('returns the sessions ordered by most recent scheduled date first', function () {
    $older = LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id, 'scheduled_date' => now()->subDays(5)->toDateString(),
    ]);
    $newer = LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->instructor->id, 'scheduled_date' => now()->subDay()->toDateString(),
    ]);

    $summary = $this->service->summarize($this->student, $this->instructor);

    expect($summary['sessions']->first()->id)->toBe($newer->id);
    expect($summary['sessions']->last()->id)->toBe($older->id);
});
```

- [ ] **Step 2: Run the test, verify it fails**

Run: `php artisan test --compact tests/Unit/Scheduling/StudentSessionSummaryServiceTest.php`
Expected: FAIL (class doesn't exist yet).

- [ ] **Step 3: Write `StudentSessionSummaryService`**

Create `app/Domain/Scheduling/Services/StudentSessionSummaryService.php`:

```php
<?php

namespace App\Domain\Scheduling\Services;

use App\Domain\Scheduling\Enums\PresenceStatus;
use App\Domain\Scheduling\Enums\SessionType;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Students\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Session duration is always computed from starts_at/ends_at at read time -
 * no stored duration column, so there is nothing to keep in sync. Scoped to
 * one (student, instructor) pair on purpose: a moniteur's route sheet for a
 * student only reflects sessions they themselves conducted.
 */
class StudentSessionSummaryService
{
    /**
     * @return array{total: int, present: int, absent: int, practicalHoursCompleted: float, sessions: \Illuminate\Support\Collection<int, LessonSession>}
     */
    public function summarize(Student $student, User $instructor): array
    {
        $sessions = LessonSession::query()
            ->where('student_id', $student->id)
            ->where('instructor_id', $instructor->id)
            ->orderByDesc('scheduled_date')
            ->get();

        $practicalHoursCompleted = $sessions
            ->filter(fn (LessonSession $session) => $session->presence === PresenceStatus::Present && $session->type === SessionType::Practical)
            ->sum(fn (LessonSession $session) => Carbon::parse($session->ends_at)->diffInMinutes(Carbon::parse($session->starts_at)) / 60);

        return [
            'total' => $sessions->count(),
            'present' => $sessions->where('presence', PresenceStatus::Present)->count(),
            'absent' => $sessions->where('presence', PresenceStatus::Absent)->count(),
            'practicalHoursCompleted' => round($practicalHoursCompleted, 2),
            'sessions' => $sessions,
        ];
    }
}
```

- [ ] **Step 4: Run the test, verify it passes**

Run: `php artisan test --compact tests/Unit/Scheduling/StudentSessionSummaryServiceTest.php`
Expected: 3 passed.

- [ ] **Step 5: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Scheduling/Services/StudentSessionSummaryService.php tests/Unit/Scheduling/StudentSessionSummaryServiceTest.php
git commit -m "feat(scheduling): add StudentSessionSummaryService for per-instructor session aggregates"
```

---

### Task 2: `EvaluationService::acquiredSummary()` — shared skill-progress summary

**Files:**
- Modify: `app/Domain/Training/Services/EvaluationService.php`
- Test: `tests/Unit/Training/EvaluationServiceTest.php` (extend)

**Interfaces:**
- Produces: `EvaluationService::acquiredSummary(Student $student): array` — returns `['acquired' => int, 'total' => int, 'percent' => int]`, consumed by Task 3's controller.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Unit/Training/EvaluationServiceTest.php` (add these `it()` blocks; add `use App\Domain\Training\Models\Skill;` to the file's imports if not already present — check first):

```php
it('summarizes acquired skills as a count and percentage', function () {
    $skillA = Skill::factory()->create(['structure_id' => $this->structure->id]);
    $skillB = Skill::factory()->create(['structure_id' => $this->structure->id]);
    Skill::factory()->create(['structure_id' => $this->structure->id]);
    Skill::factory()->create(['structure_id' => $this->structure->id]);

    $this->service->record($this->student, [
        $skillA->id => SkillLevel::Acquired->value,
        $skillB->id => SkillLevel::InProgress->value,
    ]);

    $summary = $this->service->acquiredSummary($this->student);

    expect($summary['acquired'])->toBe(1);
    expect($summary['total'])->toBe(4);
    expect($summary['percent'])->toBe(25);
});

it('summarizes zero skills as a zero percent, not a division error', function () {
    $summary = $this->service->acquiredSummary($this->student);

    expect($summary)->toBe(['acquired' => 0, 'total' => 0, 'percent' => 0]);
});
```

- [ ] **Step 2: Run the tests, verify they fail**

Run: `php artisan test --compact tests/Unit/Training/EvaluationServiceTest.php`
Expected: the two new tests FAIL (method doesn't exist), the existing ones still pass.

- [ ] **Step 3: Add `acquiredSummary()` to `EvaluationService`**

Edit `app/Domain/Training/Services/EvaluationService.php`. Add the import `use App\Domain\Training\Models\Skill;` if not already present, and add this method (place it after `record()`):

```php
/**
 * The one reusable figure every skill-progress screen needs: how many of
 * this student's skills are Acquired, out of how many exist. The
 * evaluation and eleve-progression screens each already compute their own
 * per-category subtotals inline - this is the plain overall count neither
 * of them assembles today, extracted here so a third consumer (the
 * moniteur route sheet) doesn't re-derive the join a fourth time.
 *
 * @return array{acquired: int, total: int, percent: int}
 */
public function acquiredSummary(Student $student): array
{
    $total = Skill::query()->count();

    if ($total === 0) {
        return ['acquired' => 0, 'total' => 0, 'percent' => 0];
    }

    $acquired = SkillProgress::query()
        ->where('student_id', $student->id)
        ->where('level', SkillLevel::Acquired)
        ->count();

    return [
        'acquired' => $acquired,
        'total' => $total,
        'percent' => (int) round(($acquired / $total) * 100),
    ];
}
```

- [ ] **Step 4: Run the tests, verify they pass**

Run: `php artisan test --compact tests/Unit/Training/EvaluationServiceTest.php`
Expected: all passed (existing + 2 new).

- [ ] **Step 5: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Training/Services/EvaluationService.php tests/Unit/Training/EvaluationServiceTest.php
git commit -m "feat(training): add EvaluationService::acquiredSummary() for reuse outside the evaluation screen"
```

---

### Task 3: Controller, route, policy check, view, and list link

**Files:**
- Create: `app/Domain/Scheduling/Http/Controllers/StudentRouteSheetController.php`
- Modify: `routes/web.php`
- Create: `resources/views/moniteur/feuille-route.blade.php`
- Modify: `resources/views/students/index.blade.php`
- Test: `tests/Feature/Scheduling/StudentRouteSheetTest.php` (new)

**Interfaces:**
- Consumes: `StudentSessionSummaryService::summarize()` (Task 1), `EvaluationService::acquiredSummary()` (Task 2), existing `StudentPolicy::view()`.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Scheduling/StudentRouteSheetTest.php`:

```php
<?php

use App\Domain\Scheduling\Enums\PresenceStatus;
use App\Domain\Scheduling\Enums\SessionType;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Training\Enums\SkillLevel;
use App\Domain\Training\Models\Skill;
use App\Domain\Training\Models\SkillProgress;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->moniteur->assignRole('moniteur');
    $this->student = Student::factory()->create([
        'structure_id' => $this->structure->id,
        'instructor_id' => $this->moniteur->id,
    ]);
});

it('shows a moniteur the route sheet for a student they encadre, with correct aggregates', function () {
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->moniteur->id, 'presence' => PresenceStatus::Present,
        'type' => SessionType::Practical, 'starts_at' => '08:00', 'ends_at' => '09:30',
    ]);
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->moniteur->id, 'presence' => PresenceStatus::Absent,
        'type' => SessionType::Practical, 'starts_at' => '08:00', 'ends_at' => '09:00',
    ]);
    $skill = Skill::factory()->create(['structure_id' => $this->structure->id]);
    SkillProgress::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'skill_id' => $skill->id, 'level' => SkillLevel::Acquired,
    ]);

    $response = $this->actingAs($this->moniteur)->get(route('moniteur.eleves.feuille-route', $this->student));

    $response->assertOk()
        ->assertSee('2') // total sessions
        ->assertSee('1/1') // acquired/total skills
        ->assertSee('100%');
});

it('does not let a moniteur view the route sheet of a student they do not encadre', function () {
    $otherMoniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $otherMoniteur->assignRole('moniteur');

    $this->actingAs($otherMoniteur)
        ->get(route('moniteur.eleves.feuille-route', $this->student))
        ->assertForbidden();
});

it('does not let a moniteur of another tenant view the route sheet', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $foreignMoniteur = User::factory()->create(['structure_id' => $otherStructure->id]);
    $foreignMoniteur->assignRole('moniteur');

    $this->actingAs($foreignMoniteur)
        ->get(route('moniteur.eleves.feuille-route', $this->student))
        ->assertNotFound();
});

it('does not let an admin or eleve access the moniteur-only route', function () {
    $admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('moniteur.eleves.feuille-route', $this->student))
        ->assertForbidden();
});

it('only counts sessions this moniteur personally conducted with the student', function () {
    $otherMoniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $otherMoniteur->assignRole('moniteur');

    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $this->moniteur->id, 'presence' => PresenceStatus::Present,
    ]);
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $this->student->id,
        'instructor_id' => $otherMoniteur->id, 'presence' => PresenceStatus::Present,
    ]);

    $this->actingAs($this->moniteur)->get(route('moniteur.eleves.feuille-route', $this->student))
        ->assertOk()
        ->assertDontSee('2'); // must count only 1 session, not both
});
```

- [ ] **Step 2: Run the tests, verify they fail**

Run: `php artisan test --compact tests/Feature/Scheduling/StudentRouteSheetTest.php`
Expected: FAIL (route doesn't exist yet).

- [ ] **Step 3: Write the controller**

Create `app/Domain/Scheduling/Http/Controllers/StudentRouteSheetController.php`:

```php
<?php

namespace App\Domain\Scheduling\Http\Controllers;

use App\Domain\Scheduling\Services\StudentSessionSummaryService;
use App\Domain\Students\Models\Student;
use App\Domain\Training\Services\EvaluationService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Consolidated per-student view for a moniteur: session totals/presence/
 * driving hours + detailed history + skill-progress summary. Access is
 * gated by the existing StudentPolicy::view() (already restricts a
 * moniteur to their own assigned students) - no new policy needed.
 */
class StudentRouteSheetController extends Controller
{
    public function __construct(
        private readonly StudentSessionSummaryService $sessions,
        private readonly EvaluationService $evaluation,
    ) {}

    public function show(Student $student): View
    {
        $this->authorize('view', $student);

        return view('moniteur.feuille-route', [
            'student' => $student,
            'summary' => $this->sessions->summarize($student, Auth::user()),
            'skillSummary' => $this->evaluation->acquiredSummary($student),
        ]);
    }
}
```

- [ ] **Step 4: Add the route**

Edit `routes/web.php`. Add the import `use App\Domain\Scheduling\Http\Controllers\StudentRouteSheetController;` near the other Scheduling controller imports, and add this route inside the existing `role:moniteur` group (the one with `moniteur/dashboard` and `moniteur/agenda`):

```php
Route::get('moniteur/eleves/{student}/feuille-route', [StudentRouteSheetController::class, 'show'])->name('eleves.feuille-route');
```

- [ ] **Step 5: Write the view**

Create `resources/views/moniteur/feuille-route.blade.php`:

```blade
<x-app-layout>
    <x-slot name="header">Feuille de route - {{ $student->fullName() }}</x-slot>

    <div class="py-6 max-w-3xl mx-auto space-y-5">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <x-card>
                <p class="text-content-muted text-xs">Séances totales</p>
                <p class="text-2xl font-semibold text-content mt-1">{{ $summary['total'] }}</p>
            </x-card>
            <x-card>
                <p class="text-content-muted text-xs">Présences</p>
                <p class="text-2xl font-semibold text-success mt-1">{{ $summary['present'] }}</p>
            </x-card>
            <x-card>
                <p class="text-content-muted text-xs">Absences</p>
                <p class="text-2xl font-semibold text-danger mt-1">{{ $summary['absent'] }}</p>
            </x-card>
            <x-card>
                <p class="text-content-muted text-xs">Heures de conduite</p>
                <p class="text-2xl font-semibold text-content mt-1">{{ $summary['practicalHoursCompleted'] }}h</p>
            </x-card>
        </div>

        <x-card>
            <div class="flex items-center justify-between mb-2">
                <div class="text-sm font-semibold text-content">Compétences</div>
                <a href="{{ route('training.evaluation.show', $student) }}" class="text-xs text-primary hover:underline">
                    Voir l'évaluation détaillée
                </a>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-content-secondary">{{ $skillSummary['acquired'] }}/{{ $skillSummary['total'] }} acquises</span>
                <span class="text-content font-medium">{{ $skillSummary['percent'] }}%</span>
            </div>
            <div class="bg-surface-inset rounded-full h-2 overflow-hidden mt-2">
                <div class="bg-primary h-2 rounded-full" style="width: {{ max(2, $skillSummary['percent']) }}%"></div>
            </div>
        </x-card>

        <x-card :padded="false">
            <div class="px-4 py-3 border-b border-border/60 text-sm font-semibold text-content">Historique des séances</div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-content-muted">
                        <tr>
                            <th class="px-4 py-2 font-medium">Date</th>
                            <th class="px-4 py-2 font-medium">Type</th>
                            <th class="px-4 py-2 font-medium">Horaire</th>
                            <th class="px-4 py-2 font-medium">Lieu</th>
                            <th class="px-4 py-2 font-medium">Présence</th>
                            <th class="px-4 py-2 font-medium">Note</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        @forelse ($summary['sessions'] as $session)
                            <tr>
                                <td class="px-4 py-2.5">{{ $session->scheduled_date->format('d/m/Y') }}</td>
                                <td class="px-4 py-2.5 text-content-secondary">{{ $session->type->label() }}</td>
                                <td class="px-4 py-2.5 text-content-secondary">{{ $session->starts_at }}–{{ $session->ends_at }}</td>
                                <td class="px-4 py-2.5 text-content-secondary">{{ $session->location ?? '-' }}</td>
                                <td class="px-4 py-2.5">
                                    <x-badge :variant="$session->presence->value === 'present' ? 'success' : ($session->presence->value === 'absent' ? 'danger' : 'neutral')">
                                        {{ $session->presence->label() }}
                                    </x-badge>
                                </td>
                                <td class="px-4 py-2.5 text-content-secondary">{{ $session->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-content-muted">Aucune séance pour le moment.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-app-layout>
```

- [ ] **Step 6: Add the link from the student list, visible only to a moniteur**

Edit `resources/views/students/index.blade.php`. Add a header column and a per-row link, moniteur-only (an admin already has the full profile at `students.show`, this consolidated view is specifically the moniteur's own screen per the prompt's exact scope: "accessible depuis la liste des élèves du moniteur"). Find the `<th class="px-5 py-3 font-medium">Moniteur</th>` header line and add after it:

```blade
@if (auth()->user()->hasRole('moniteur'))
    <th class="px-5 py-3 font-medium"></th>
@endif
```

Find the closing `</tr>` of the data row (right after the `Moniteur` `<td>`) and add before it:

```blade
@if (auth()->user()->hasRole('moniteur'))
    <td class="px-5 py-3 text-right">
        <a href="{{ route('moniteur.eleves.feuille-route', $student) }}" class="text-xs text-primary hover:underline">Feuille de route</a>
    </td>
@endif
```

Also update the `<x-empty-table-row colspan="5" ...>` to `colspan="6"` — but only when the moniteur column is present. Simplest correct fix: change the hardcoded `colspan="5"` to `colspan="{{ auth()->user()->hasRole('moniteur') ? 6 : 5 }}"`.

- [ ] **Step 7: Run the tests**

Run: `php artisan test --compact tests/Feature/Scheduling/StudentRouteSheetTest.php`
Expected: 5 passed.

Run: `php artisan test --compact tests/Feature/Students tests/Feature/Scheduling` (confirms the `students/index.blade.php` edit didn't break existing student-index tests, e.g. `StudentIndexEmptyStateTest.php`, `StudentIndexFilterTest.php`, `StudentTenantIsolationTest.php`)
Expected: all passed.

- [ ] **Step 8: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Scheduling/Http/Controllers/StudentRouteSheetController.php \
        routes/web.php \
        resources/views/moniteur/feuille-route.blade.php \
        resources/views/students/index.blade.php \
        tests/Feature/Scheduling/StudentRouteSheetTest.php
git commit -m "feat(scheduling): add the moniteur route-sheet screen per student"
```

---

### Task 4: Whole-branch verification

**Files:** none (verification only).

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: all passed, including `tests/Architecture/DomainBoundariesTest.php` — `StudentRouteSheetController` (Scheduling domain) depends on `App\Domain\Training\Services\EvaluationService` (Training domain). Confirm this doesn't trip any rule: check the arch test for "Scheduling domain does not depend on..." rules and verify Training is not in that exclusion list before assuming it's fine.

- [ ] **Step 2: Manually confirm the acceptance criteria**

Using the browser or Tinker: as a moniteur, create/verify a few practical sessions (some present, some absent) and a skill evaluation for an assigned student, open their feuille de route, and confirm the totals/hours/skill summary match exactly what's in the database; confirm the link is absent from the student list for an admin; confirm attempting the route for an unassigned student 403s.
