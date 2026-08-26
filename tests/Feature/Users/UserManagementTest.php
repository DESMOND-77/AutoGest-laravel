<?php

use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

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

it('ignores an invalid role query param instead of erroring', function () {
    $this->actingAs($this->admin)->get(route('settings.users.index', ['role' => 'not-a-real-role']))
        ->assertOk()
        ->assertSee($this->admin->name);
});

it('lets an admin trigger a password-reset link for an existing user', function () {
    Notification::fake();
    $target = User::factory()->create(['structure_id' => $this->structure->id]);

    $this->actingAs($this->admin)->post(route('settings.users.reset-password', $target))->assertRedirect();

    Notification::assertSentTo($target, ResetPassword::class);
});

it('shows a throttled error instead of a false success flash when the reset link cannot be sent', function () {
    $target = User::factory()->create(['structure_id' => $this->structure->id]);

    Password::shouldReceive('sendResetLink')
        ->once()
        ->with(['email' => $target->email])
        ->andReturn(Password::RESET_THROTTLED);

    $this->actingAs($this->admin)->post(route('settings.users.reset-password', $target))
        ->assertRedirect()
        ->assertSessionHasErrors('user');
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

it('hides the deactivate button on the current admin\'s own row', function () {
    $this->actingAs($this->admin)->get(route('settings.users.index'))
        ->assertOk()
        ->assertDontSee(route('settings.users.deactivate', $this->admin), false);
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

    $this->actingAs($this->admin)->post(route('settings.users.reset-password', $target))->assertNotFound();
    $this->actingAs($this->admin)->post(route('settings.users.deactivate', $target))->assertNotFound();
    $this->actingAs($this->admin)->post(route('settings.users.reactivate', $target))->assertNotFound();
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
