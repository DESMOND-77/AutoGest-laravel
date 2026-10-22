# Skill Category Grouping + Validation Date Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Group the evaluation screen's skills by `category` with a `x/y acquises` subtotal per category, and fix the existing (buggy) validation-date tracking so it's set once on first acquisition, preserved across resubmission, and cleared on regression — then apply the same grouping/date display to the eleve's own "Ma Progression" screen.

**Architecture:** No new migration and no new column. `skill_progress.validated_at` (nullable date, already in the schema and already cast on the model) already does the job the prompt's `acquired_at` column would have done — it's just currently written incorrectly (unconditionally overwritten to `now()` on every save where the level is Acquired, never cleared on regression). This plan fixes `EvaluationService::record()`'s write logic in place rather than adding a duplicate column, per the prompt's own "ne pas sur-ingénierer" instruction and this project's `docs/audit/business-workflow.md`-staleness precedent (trust the current code's actual behavior over a prompt/doc description written before that code existed). The grouping is a pure view/controller-data concern — `$skills->groupBy('category')`.

**Tech Stack:** Laravel 12, PHP 8.5, Pest 3, Blade.

**Spec:** `Promptset/07-competences-categories-date-validation.md`, with one deliberate deviation from its literal text (documented above and in Task 1): reuse `validated_at` instead of adding `acquired_at`.

## Global Constraints

- `EvaluationService` already exists as the sole write path for `SkillProgress` rows (`EvaluationController::store()` calls `$this->evaluation->record()`) — fix the logic there, do not create a new service for this one rule.
- `validated_at` must be set to `now()->toDateString()` only when a skill's level transitions **into** `Acquired` from something else (or from having no row at all). It must be **preserved** (not reset to a new `now()`) when a resubmission keeps the level at `Acquired`. It must be **cleared to `null`** whenever the level moves away from `Acquired` (to `InProgress` or `NotStarted`).
- Do not touch `SkillProgress`'s `$fillable`/`$casts` — `validated_at` is already fillable and already cast as `date`; no model changes needed.
- No arrow glyphs (←/→) in any UI copy.
- Every change must have a passing Pest test. Run `vendor/bin/pint --dirty --format agent` after PHP edits.
- Do not break `tests/Feature/Training/EvaluationAuthorizationTest.php` or `tests/Feature/Students/EleveSelfServiceTest.php` — both already exercise `SkillProgress`/the progression screen.

---

### Task 1: Fix `validated_at` write logic in `EvaluationService`

**Files:**
- Modify: `app/Domain/Training/Services/EvaluationService.php`
- Test: `tests/Unit/Training/EvaluationServiceTest.php` (new)

**Interfaces:**
- Consumes: nothing new.
- Produces: `EvaluationService::record()`'s corrected behavior, relied on by Task 2/3's view changes (which read `$progress->validated_at`).

- [ ] **Step 1: Write the failing tests**

Create `tests/Unit/Training/EvaluationServiceTest.php`:

```php
<?php

use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Training\Enums\SkillLevel;
use App\Domain\Training\Models\Skill;
use App\Domain\Training\Models\SkillProgress;
use App\Domain\Training\Services\EvaluationService;
use Carbon\Carbon;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    $this->student = Student::factory()->create(['structure_id' => $this->structure->id]);
    $this->skill = Skill::factory()->create(['structure_id' => $this->structure->id]);
    $this->service = new EvaluationService;
});

it('sets validated_at when a skill first becomes acquired', function () {
    Carbon::setTestNow('2026-07-21 10:00:00');

    $this->service->record($this->student, [$this->skill->id => SkillLevel::Acquired->value]);

    $progress = SkillProgress::query()->where('student_id', $this->student->id)->sole();
    expect($progress->validated_at->toDateString())->toBe('2026-07-21');

    Carbon::setTestNow();
});

it('does not change validated_at when a resubmission keeps the level at acquired', function () {
    Carbon::setTestNow('2026-07-21 10:00:00');
    $this->service->record($this->student, [$this->skill->id => SkillLevel::Acquired->value]);

    Carbon::setTestNow('2026-08-15 10:00:00');
    $this->service->record($this->student, [$this->skill->id => SkillLevel::Acquired->value]);

    $progress = SkillProgress::query()->where('student_id', $this->student->id)->sole();
    expect($progress->validated_at->toDateString())->toBe('2026-07-21');

    Carbon::setTestNow();
});

it('clears validated_at when a skill regresses from acquired to in_progress', function () {
    $this->service->record($this->student, [$this->skill->id => SkillLevel::Acquired->value]);
    $this->service->record($this->student, [$this->skill->id => SkillLevel::InProgress->value]);

    $progress = SkillProgress::query()->where('student_id', $this->student->id)->sole();
    expect($progress->level)->toBe(SkillLevel::InProgress);
    expect($progress->validated_at)->toBeNull();
});

it('clears validated_at when a skill regresses from acquired to not_started', function () {
    $this->service->record($this->student, [$this->skill->id => SkillLevel::Acquired->value]);
    $this->service->record($this->student, [$this->skill->id => SkillLevel::NotStarted->value]);

    $progress = SkillProgress::query()->where('student_id', $this->student->id)->sole();
    expect($progress->validated_at)->toBeNull();
});

it('leaves validated_at null for a skill that has never been acquired', function () {
    $this->service->record($this->student, [$this->skill->id => SkillLevel::InProgress->value]);

    $progress = SkillProgress::query()->where('student_id', $this->student->id)->sole();
    expect($progress->validated_at)->toBeNull();
});
```

- [ ] **Step 2: Run the tests, confirm they fail**

Run: `php artisan test --compact tests/Unit/Training/EvaluationServiceTest.php`
Expected: the "resubmission keeps the same date" and "regression clears the date" tests FAIL — the current unconditional `now()->toDateString()` write means every acquired-and-resubmitted skill gets today's date, and the current code writes `null` correctly on regression only by accident (it's `$skillLevel === Acquired ? now() : null`, which already nulls on regression — verify this one might actually already pass; that's fine, it just confirms the existing partial-correctness).

- [ ] **Step 3: Fix `EvaluationService::record()`**

Replace the full content of `app/Domain/Training/Services/EvaluationService.php`:

```php
<?php

namespace App\Domain\Training\Services;

use App\Domain\Students\Models\Student;
use App\Domain\Training\Enums\SkillLevel;
use App\Domain\Training\Models\SkillProgress;
use App\Models\User;

/**
 * The legacy moniteur/evaluation.php looked up the target student with no
 * ownership check at all (see fixs.md #4) - the fix there was in the Policy
 * layer, not here. This service just does the actual upsert, one row per
 * skill, exactly once per (student, skill) pair via the unique constraint.
 *
 * validated_at is set once, on the transition INTO Acquired - not
 * overwritten on every resubmission that keeps a skill Acquired, and
 * cleared whenever a skill moves away from Acquired (a moniteur correcting
 * a premature validation).
 */
class EvaluationService
{
    /**
     * @param  array<int, string>  $levels  skill_id => SkillLevel value
     */
    public function record(Student $student, array $levels, ?User $instructor = null): void
    {
        foreach ($levels as $skillId => $level) {
            $skillLevel = SkillLevel::from($level);

            $existing = SkillProgress::query()
                ->where('student_id', $student->id)
                ->where('skill_id', (int) $skillId)
                ->first();

            $validatedAt = match (true) {
                $skillLevel !== SkillLevel::Acquired => null,
                $existing?->level === SkillLevel::Acquired => $existing->validated_at,
                default => now()->toDateString(),
            };

            SkillProgress::query()->updateOrCreate(
                ['student_id' => $student->id, 'skill_id' => (int) $skillId],
                [
                    'instructor_id' => $instructor?->id,
                    'level' => $skillLevel->value,
                    'validated_at' => $validatedAt,
                ]
            );
        }
    }
}
```

- [ ] **Step 4: Run the tests, confirm they pass**

Run: `php artisan test --compact tests/Unit/Training/EvaluationServiceTest.php`
Expected: 5 passed.

Run: `php artisan test --compact tests/Feature/Training/EvaluationAuthorizationTest.php`
Expected: all passed (unchanged behavior for that file's scenarios — it never resubmits the same skill twice).

- [ ] **Step 5: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Training/Services/EvaluationService.php tests/Unit/Training/EvaluationServiceTest.php
git commit -m "fix(training): stop overwriting validated_at on every acquired resubmission"
```

---

### Task 2: Group the moniteur/admin evaluation screen by category with subtotals and validation dates

**Files:**
- Modify: `app/Domain/Training/Http/Controllers/EvaluationController.php`
- Modify: `resources/views/training/evaluation/show.blade.php`
- Test: `tests/Feature/Training/EvaluationGroupingTest.php` (new)

**Interfaces:**
- Consumes: `SkillProgress::$validated_at` (fixed by Task 1).
- Produces: nothing new consumed elsewhere.

- [ ] **Step 1: Group skills by category in the controller**

Edit `app/Domain/Training/Http/Controllers/EvaluationController.php`. Replace `show()`:

```php
public function show(Student $student): View
{
    $this->authorize('evaluate', $student);

    $skillsByCategory = Skill::query()->orderBy('category')->orderBy('position')->get()->groupBy('category');

    return view('training.evaluation.show', [
        'student' => $student,
        'skillsByCategory' => $skillsByCategory,
        'progress' => SkillProgress::query()->where('student_id', $student->id)->get()->keyBy('skill_id'),
    ]);
}
```

(`store()` is unchanged — it already only needs `$request`/`$student`.)

- [ ] **Step 2: Rewrite the evaluation view to render grouped sections with subtotals and dates**

Replace the full content of `resources/views/training/evaluation/show.blade.php`:

```blade
<x-app-layout>
    <x-slot name="header">Évaluation - {{ $student->fullName() }}</x-slot>

    <div class="py-6 max-w-2xl mx-auto space-y-5">
        @if (session('status'))
            <x-alert variant="success" class="mb-4">{{ session('status') }}</x-alert>
        @endif

        @if ($skillsByCategory->isEmpty())
            <x-card>
                <p class="text-sm text-content-secondary">Aucune compétence définie pour cet établissement.</p>
            </x-card>
        @else
            @php $levelPercent = ['not_started' => 0, 'in_progress' => 50, 'acquired' => 100]; @endphp
            <form method="POST" action="{{ route('training.evaluation.store', $student) }}" class="space-y-5">
                @csrf
                @foreach ($skillsByCategory as $category => $skills)
                    @php $acquiredCount = $skills->filter(fn ($skill) => ($progress->get($skill->id)?->level?->value ?? 'not_started') === 'acquired')->count(); @endphp
                    <x-card :padded="false">
                        <div class="px-4 py-3 flex items-center justify-between border-b border-border/60">
                            <h2 class="text-sm font-semibold text-content">{{ $category }}</h2>
                            <span class="text-xs font-medium text-content-secondary">{{ $acquiredCount }}/{{ $skills->count() }} acquises</span>
                        </div>
                        <div class="divide-y divide-border/60">
                            @foreach ($skills as $skill)
                                @php $current = $progress->get($skill->id)?->level?->value ?? 'not_started'; @endphp
                                <div class="p-4">
                                    <div class="flex items-center justify-between gap-4 mb-2">
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-content">{{ $skill->label }}</div>
                                            @if ($current === 'acquired' && $progress->get($skill->id)?->validated_at)
                                                <div class="text-xs text-success mt-0.5">Validé le {{ $progress->get($skill->id)->validated_at->format('d/m/Y') }}</div>
                                            @endif
                                        </div>
                                        <div class="flex gap-3 text-sm shrink-0">
                                            @foreach (\App\Domain\Training\Enums\SkillLevel::cases() as $level)
                                                <label class="flex items-center gap-1.5 text-content-secondary">
                                                    <input type="radio" name="levels[{{ $skill->id }}]" value="{{ $level->value }}" @checked($current === $level->value) class="text-primary focus:ring-primary">
                                                    {{ $level->label() }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="bg-surface-inset rounded-full h-1.5 overflow-hidden">
                                        <div @class([
                                            'h-1.5 rounded-full',
                                            'bg-content-muted' => $current === 'not_started',
                                            'bg-warning' => $current === 'in_progress',
                                            'bg-success' => $current === 'acquired',
                                        ]) style="width: {{ max(2, $levelPercent[$current]) }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-card>
                @endforeach

                <div class="flex justify-end">
                    <x-primary-button>Enregistrer l'évaluation</x-primary-button>
                </div>
            </form>
        @endif
    </div>
</x-app-layout>
```

- [ ] **Step 3: Write the feature test**

Create `tests/Feature/Training/EvaluationGroupingTest.php`:

```php
<?php

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

it('groups skills by category with an acquired subtotal', function () {
    $skillA = Skill::factory()->create(['structure_id' => $this->structure->id, 'category' => 'Circulation', 'label' => 'Priorités']);
    $skillB = Skill::factory()->create(['structure_id' => $this->structure->id, 'category' => 'Circulation', 'label' => 'Ronds-points']);
    $skillC = Skill::factory()->create(['structure_id' => $this->structure->id, 'category' => 'Maniabilité', 'label' => 'Créneau']);

    SkillProgress::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->student->id,
        'skill_id' => $skillA->id,
        'level' => SkillLevel::Acquired,
        'validated_at' => '2026-07-21',
    ]);

    $response = $this->actingAs($this->moniteur)->get(route('training.evaluation.show', $this->student));

    $response->assertOk()
        ->assertSeeInOrder(['Circulation', '1/2 acquises'])
        ->assertSeeInOrder(['Maniabilité', '0/1 acquises'])
        ->assertSee('Validé le 21/07/2026');
});

it('does not show a validation date for a skill that is not acquired', function () {
    $skill = Skill::factory()->create(['structure_id' => $this->structure->id, 'category' => 'Circulation']);

    $this->actingAs($this->moniteur)->get(route('training.evaluation.show', $this->student))
        ->assertOk()
        ->assertDontSee('Validé le');
});
```

- [ ] **Step 4: Run the tests**

Run: `php artisan test --compact tests/Feature/Training/EvaluationGroupingTest.php`
Expected: 2 passed.

Run: `php artisan test --compact tests/Feature/Training`
Expected: all passed (confirms `EvaluationAuthorizationTest.php` still works against the new `show()` — it never asserts on skill count/text, only on HTTP status and DB state, so it should be unaffected).

- [ ] **Step 5: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Training/Http/Controllers/EvaluationController.php \
        resources/views/training/evaluation/show.blade.php \
        tests/Feature/Training/EvaluationGroupingTest.php
git commit -m "feat(training): group the evaluation screen by category with subtotals and validation dates"
```

---

### Task 3: Apply the same grouping/date display to the eleve "Ma Progression" screen

**Files:**
- Modify: `app/Domain/Training/Http/Controllers/StudentProgressionController.php`
- Modify: `resources/views/eleve/progression.blade.php`
- Test: `tests/Feature/Students/EleveSelfServiceTest.php` (extend)

**Interfaces:**
- Consumes: same `SkillProgress::$validated_at` and grouping approach as Task 2.

- [ ] **Step 1: Group skills by category in the controller, keep the overall percentage**

Edit `app/Domain/Training/Http/Controllers/StudentProgressionController.php`. Replace `__invoke()`:

```php
public function __invoke(): View
{
    $student = Student::query()->where('user_id', Auth::id())->first();

    $skills = Skill::query()->orderBy('category')->orderBy('position')->get();
    $progress = $student
        ? SkillProgress::query()->where('student_id', $student->id)->get()->keyBy('skill_id')
        : collect();

    $levelPercent = ['not_started' => 0, 'in_progress' => 50, 'acquired' => 100];
    $overallPercent = $skills->isEmpty()
        ? 0
        : (int) round($skills->avg(fn (Skill $skill) => $levelPercent[$progress->get($skill->id)?->level?->value ?? 'not_started']));

    return view('eleve.progression', [
        'student' => $student,
        'skillsByCategory' => $skills->groupBy('category'),
        'progress' => $progress,
        'overallPercent' => $overallPercent,
    ]);
}
```

- [ ] **Step 2: Rewrite the eleve progression view with category sections and subtotals**

Replace the full content of `resources/views/eleve/progression.blade.php`:

```blade
<x-app-layout>
    <x-slot name="header">Ma progression</x-slot>

    <div class="py-6 max-w-2xl mx-auto space-y-5">
        @if ($skillsByCategory->isEmpty())
            <x-card>
                <p class="text-sm text-content-secondary">Aucune compétence définie pour votre établissement.</p>
            </x-card>
        @else
            <x-card>
                <div class="flex items-center justify-between text-xs text-content-muted mb-1">
                    <span>Progression globale</span>
                    <span>{{ $overallPercent }}%</span>
                </div>
                <div class="bg-surface-inset rounded-full h-2 overflow-hidden">
                    <div class="bg-primary h-2 rounded-full" style="width: {{ max(2, $overallPercent) }}%"></div>
                </div>
            </x-card>

            @php $levelPercent = ['not_started' => 0, 'in_progress' => 50, 'acquired' => 100]; @endphp
            @foreach ($skillsByCategory as $category => $skills)
                @php $acquiredCount = $skills->filter(fn ($skill) => ($progress->get($skill->id)?->level?->value ?? 'not_started') === 'acquired')->count(); @endphp
                <x-card :padded="false">
                    <div class="px-4 py-3 flex items-center justify-between border-b border-border/60">
                        <h2 class="text-sm font-semibold text-content">{{ $category }}</h2>
                        <span class="text-xs font-medium text-content-secondary">{{ $acquiredCount }}/{{ $skills->count() }} acquises</span>
                    </div>
                    <div class="divide-y divide-border/60">
                        @foreach ($skills as $skill)
                            @php $current = $progress->get($skill->id)?->level?->value ?? 'not_started'; @endphp
                            <div class="p-4">
                                <div class="flex items-center justify-between gap-4 mb-2">
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium text-content">{{ $skill->label }}</div>
                                        @if ($current === 'acquired' && $progress->get($skill->id)?->validated_at)
                                            <div class="text-xs text-success mt-0.5">Validé le {{ $progress->get($skill->id)->validated_at->format('d/m/Y') }}</div>
                                        @endif
                                    </div>
                                    <span @class([
                                        'text-xs font-semibold px-2 py-0.5 rounded-full shrink-0',
                                        'bg-surface-inset text-content-muted' => $current === 'not_started',
                                        'bg-warning/10 text-warning' => $current === 'in_progress',
                                        'bg-success/10 text-success' => $current === 'acquired',
                                    ])>{{ \App\Domain\Training\Enums\SkillLevel::from($current)->label() }}</span>
                                </div>
                                <div class="bg-surface-inset rounded-full h-1.5 overflow-hidden">
                                    <div @class([
                                        'h-1.5 rounded-full',
                                        'bg-content-muted' => $current === 'not_started',
                                        'bg-warning' => $current === 'in_progress',
                                        'bg-success' => $current === 'acquired',
                                    ]) style="width: {{ max(2, $levelPercent[$current]) }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endforeach
        @endif
    </div>
</x-app-layout>
```

- [ ] **Step 3: Extend the eleve self-service test**

Edit `tests/Feature/Students/EleveSelfServiceTest.php`. Replace the existing test `'lets an eleve see their own skill progression'` with a version that also asserts category grouping and the validation date:

```php
it('lets an eleve see their own skill progression, grouped by category with a validation date', function () {
    $skill = Skill::factory()->create(['structure_id' => $this->structure->id, 'category' => 'Circulation', 'label' => 'Créneau']);
    SkillProgress::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->student->id,
        'skill_id' => $skill->id,
        'level' => SkillLevel::Acquired,
        'validated_at' => '2026-07-21',
    ]);

    $this->actingAs($this->eleve)->get(route('eleve.progression'))
        ->assertOk()
        ->assertSeeInOrder(['Circulation', '1/1 acquises', 'Créneau', 'Validé le 21/07/2026']);
});
```

- [ ] **Step 4: Run the tests**

Run: `php artisan test --compact tests/Feature/Students/EleveSelfServiceTest.php`
Expected: 8 passed (unchanged count, one test's body replaced).

Run: `php artisan test --compact tests/Feature/Training tests/Feature/Students`
Expected: all passed.

- [ ] **Step 5: Format and commit**

Run: `vendor/bin/pint --dirty --format agent`

```bash
git add app/Domain/Training/Http/Controllers/StudentProgressionController.php \
        resources/views/eleve/progression.blade.php \
        tests/Feature/Students/EleveSelfServiceTest.php
git commit -m "feat(students): group Ma Progression by category, matching the moniteur evaluation screen"
```

---

### Task 4: Whole-branch verification

**Files:** none (verification only).

- [ ] **Step 1: Run the full test suite**

Run: `php artisan test --compact`
Expected: all passed.

- [ ] **Step 2: Manually confirm the acceptance criteria**

Using the browser or Tinker: mark a skill Acquired, confirm a validation date appears; resubmit the same evaluation with the same level, confirm the date does NOT change; move the skill back to "En cours", confirm the date disappears; confirm both the moniteur evaluation screen and the eleve "Ma Progression" screen show identical category groupings and `x/y acquises` subtotals for the same student.
