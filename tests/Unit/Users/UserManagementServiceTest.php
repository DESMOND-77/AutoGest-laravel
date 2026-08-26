<?php

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Users\Services\UserManagementService;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

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

    expect(Hash::check('password', $user->password))->toBeFalse();
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
