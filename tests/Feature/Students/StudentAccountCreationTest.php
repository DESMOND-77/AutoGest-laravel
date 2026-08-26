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

it('creates a linked eleve account and emails a password-reset link when an admin creates a student', function () {
    Notification::fake();

    $response = $this->actingAs($this->admin)->post(route('students.store'), [
        'first_name' => 'Awa',
        'last_name' => 'Diallo',
        'email' => 'awa.diallo@example.com',
        'license_category' => 'B',
        'course_type' => 'normal',
    ]);

    $student = Student::query()->where('email', 'awa.diallo@example.com')->firstOrFail();
    $response->assertRedirect(route('students.show', $student));

    expect($student->user_id)->not->toBeNull();

    $user = User::query()->findOrFail($student->user_id);
    expect($user->hasRole('eleve'))->toBeTrue();
    expect($user->name)->toBe('Awa Diallo');

    Notification::assertSentTo($user, ResetPassword::class);
});

it('rejects a student email that already belongs to another account in the same tenant', function () {
    User::factory()->create(['structure_id' => $this->structure->id, 'email' => 'taken@example.com']);

    $response = $this->actingAs($this->admin)->post(route('students.store'), [
        'first_name' => 'Awa',
        'last_name' => 'Diallo',
        'email' => 'taken@example.com',
        'license_category' => 'B',
        'course_type' => 'normal',
    ]);

    $response->assertSessionHasErrors('email');
    expect(Student::query()->where('email', 'taken@example.com')->exists())->toBeFalse();
});

it('allows the same email to be reused by a student in a different tenant', function () {
    $otherStructure = Structure::factory()->create();
    User::factory()->create(['structure_id' => $otherStructure->id, 'email' => 'shared@example.com']);

    $this->actingAs($this->admin)->post(route('students.store'), [
        'first_name' => 'Awa',
        'last_name' => 'Diallo',
        'email' => 'shared@example.com',
        'license_category' => 'B',
        'course_type' => 'normal',
    ])->assertRedirect();

    expect(Student::query()->where('email', 'shared@example.com')->where('structure_id', $this->structure->id)->exists())->toBeTrue();
});

it('lets an admin create an account for an existing student with no login yet', function () {
    Notification::fake();
    $student = Student::factory()->create(['structure_id' => $this->structure->id, 'email' => 'legacy@example.com']);

    $this->actingAs($this->admin)
        ->post(route('students.create-account', $student))
        ->assertRedirect();

    $student->refresh();
    expect($student->user_id)->not->toBeNull();
    Notification::assertSentTo(User::query()->findOrFail($student->user_id), ResetPassword::class);
});

it('refuses to create a second account for a student that already has one', function () {
    $existingUser = User::factory()->create(['structure_id' => $this->structure->id]);
    $student = Student::factory()->create(['structure_id' => $this->structure->id, 'user_id' => $existingUser->id]);

    $this->actingAs($this->admin)
        ->post(route('students.create-account', $student))
        ->assertSessionHasErrors('account');
});

it('refuses to create an account for a student with no email on file', function () {
    $student = Student::factory()->create(['structure_id' => $this->structure->id, 'email' => null]);

    $this->actingAs($this->admin)
        ->post(route('students.create-account', $student))
        ->assertSessionHasErrors('account');

    expect($student->fresh()->user_id)->toBeNull();
});
