<?php

use App\Domain\Scheduling\Enums\PresenceStatus;
use App\Domain\Scheduling\Enums\SessionType;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create();
    $this->fixturePath = base_path('tests/Fixtures/legacy-planning');

    $this->moniteur = User::factory()->create(['structure_id' => $this->structure->id, 'name' => 'Alice Nguema']);
    $this->moniteur->assignRole('moniteur');

    $this->student = Student::factory()->create(['structure_id' => $this->structure->id, 'last_name' => 'DUPONT']);
});

it('imports the clean rows from the practical and theoretical grids', function () {
    $this->artisan('import:legacy-planning', [
        'structure' => $this->structure->id,
        'path' => $this->fixturePath,
    ])->assertSuccessful();

    TenantContext::set($this->structure);

    // etp1.csv 11h00-12h00 DUPONT: Présente lundi, Absente mardi.
    $practicalSessions = LessonSession::query()->where('type', SessionType::Practical)->get();
    expect($practicalSessions)->toHaveCount(2);

    $monday = $practicalSessions->first(fn ($s) => $s->scheduled_date->toDateString() === '2026-03-09');
    expect($monday)->not->toBeNull();
    expect($monday->presence)->toBe(PresenceStatus::Present);
    expect($monday->starts_at)->toBe('11:00:00');
    expect($monday->ends_at)->toBe('12:00:00');
    expect($monday->student_id)->toBe($this->student->id);
    expect($monday->instructor_id)->toBe($this->moniteur->id);

    $tuesday = $practicalSessions->first(fn ($s) => $s->scheduled_date->toDateString() === '2026-03-10');
    expect($tuesday->presence)->toBe(PresenceStatus::Absent);

    // ett1.csv 16h00-17h00 DUPONT: Nul lundi (skipped, not an error),
    // Annulé mardi, Présente mercredi.
    $theoreticalSessions = LessonSession::query()->where('type', SessionType::Theoretical)->get();
    expect($theoreticalSessions)->toHaveCount(2);

    $cancelled = $theoreticalSessions->first(fn ($s) => $s->scheduled_date->toDateString() === '2026-03-10');
    expect($cancelled->presence)->toBe(PresenceStatus::Cancelled);

    $present = $theoreticalSessions->first(fn ($s) => $s->scheduled_date->toDateString() === '2026-03-11');
    expect($present->presence)->toBe(PresenceStatus::Present);

    TenantContext::clear();
});

it('skips rows with more than one student in the same cell', function () {
    $this->artisan('import:legacy-planning', [
        'structure' => $this->structure->id,
        'path' => $this->fixturePath,
    ])->assertSuccessful();

    TenantContext::set($this->structure);
    // etp1.csv 12h00-13h00 has "MARTIN DUPONT" — two tokens, ambiguous.
    expect(LessonSession::query()->where('starts_at', '12:00:00')->exists())->toBeFalse();
    TenantContext::clear();
});

it('skips rows with more than one moniteur in the same cell', function () {
    $this->artisan('import:legacy-planning', [
        'structure' => $this->structure->id,
        'path' => $this->fixturePath,
    ])->assertSuccessful();

    TenantContext::set($this->structure);
    // ett1.csv 17h00-18h00 has "M Alice         M Bob" — ambiguous.
    expect(LessonSession::query()->where('starts_at', '17:00:00')->exists())->toBeFalse();
    TenantContext::clear();
});

it('skips a day cell with a status it does not recognize', function () {
    $this->artisan('import:legacy-planning', [
        'structure' => $this->structure->id,
        'path' => $this->fixturePath,
    ])->assertSuccessful();

    TenantContext::set($this->structure);
    // ett1.csv 18h00-19h00 jeudi has "Inconnu" — not a known status.
    expect(LessonSession::query()->where('starts_at', '18:00:00')->exists())->toBeFalse();
    TenantContext::clear();
});

it('reports the "M <first name>" single moniteur cell as unambiguous', function () {
    $this->artisan('import:legacy-planning', [
        'structure' => $this->structure->id,
        'path' => $this->fixturePath,
    ])->assertSuccessful();

    TenantContext::set($this->structure);
    expect(LessonSession::query()->where('instructor_id', $this->moniteur->id)->count())->toBe(4);
    TenantContext::clear();
});

it('skips an unmatched student without importing anything for that row', function () {
    Student::query()->where('id', $this->student->id)->delete();

    $this->artisan('import:legacy-planning', [
        'structure' => $this->structure->id,
        'path' => $this->fixturePath,
    ])->assertSuccessful();

    TenantContext::set($this->structure);
    expect(LessonSession::query()->count())->toBe(0);
    TenantContext::clear();
});

it('is idempotent: running it twice does not duplicate sessions, including cancelled ones', function () {
    $this->artisan('import:legacy-planning', [
        'structure' => $this->structure->id,
        'path' => $this->fixturePath,
    ])->assertSuccessful();

    TenantContext::set($this->structure);
    $countAfterFirstRun = LessonSession::query()->count();
    TenantContext::clear();

    $this->artisan('import:legacy-planning', [
        'structure' => $this->structure->id,
        'path' => $this->fixturePath,
    ])->assertSuccessful();

    TenantContext::set($this->structure);
    expect(LessonSession::query()->count())->toBe($countAfterFirstRun);
    TenantContext::clear();
});

it('does not write anything in --dry-run mode', function () {
    $this->artisan('import:legacy-planning', [
        'structure' => $this->structure->id,
        'path' => $this->fixturePath,
        '--dry-run' => true,
    ])->assertSuccessful();

    TenantContext::set($this->structure);
    expect(LessonSession::query()->count())->toBe(0);
    TenantContext::clear();
});

it('fails cleanly for an unknown structure', function () {
    $this->artisan('import:legacy-planning', [
        'structure' => 999999,
        'path' => $this->fixturePath,
    ])->assertFailed();
});

it('fails cleanly when the directory has no etp/ett csv files', function () {
    $this->artisan('import:legacy-planning', [
        'structure' => $this->structure->id,
        'path' => base_path('tests/Fixtures/legacy-import'),
    ])->assertFailed();
});
