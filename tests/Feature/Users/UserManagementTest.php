<?php

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

// --- Policy denial --------------------------------------------------------

it('denies a moniteur from accessing the account-management screen entirely', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->get(route('settings.users.index'))->assertForbidden();
    $this->actingAs($moniteur)->post(route('settings.users.store'), [
        'name' => 'Test', 'email' => 'x@example.com',
    ])->assertForbidden();
});
