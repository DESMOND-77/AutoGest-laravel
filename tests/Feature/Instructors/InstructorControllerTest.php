<?php

use App\Domain\Instructors\Models\Instructor;
use App\Domain\Instructors\Repositories\InstructorRepositoryInterface;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
});

it('lets an admin create a moniteur account and its instructor profile together', function () {
    Notification::fake();

    $this->actingAs($this->admin)
        ->post(route('instructors.store'), [
            'name' => 'Jean Moniteur',
            'email' => 'jean.moniteur@example.com',
            'license_number' => 'MON-0001',
            'hire_date' => '2024-01-15',
        ])
        ->assertRedirect(route('instructors.index'));

    $user = User::query()->where('email', 'jean.moniteur@example.com')->firstOrFail();
    expect($user->hasRole('moniteur'))->toBeTrue();
    expect(Instructor::query()->where('user_id', $user->id)->where('license_number', 'MON-0001')->exists())->toBeTrue();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('does not leave an orphaned user account when the instructor profile creation fails', function () {
    Notification::fake();

    $this->mock(InstructorRepositoryInterface::class, function ($mock) {
        $mock->shouldReceive('create')->once()->andThrow(new RuntimeException('boom'));
    });

    $this->actingAs($this->admin)
        ->post(route('instructors.store'), [
            'name' => 'Jean Moniteur',
            'email' => 'jean.moniteur@example.com',
            'license_number' => 'MON-0001',
            'hire_date' => '2024-01-15',
        ])
        ->assertSessionHasErrors('instructor');

    expect(User::query()->where('email', 'jean.moniteur@example.com')->exists())->toBeFalse();
    expect(Instructor::query()->count())->toBe(0);
});

it('rejects an instructor email that already belongs to another account in the same tenant', function () {
    User::factory()->create(['structure_id' => $this->structure->id, 'email' => 'taken@example.com']);

    $this->actingAs($this->admin)
        ->post(route('instructors.store'), [
            'name' => 'Jean Moniteur',
            'email' => 'taken@example.com',
        ])
        ->assertSessionHasErrors('email');

    expect(Instructor::query()->count())->toBe(0);
});

it('lets an admin list instructors for their own school', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    Instructor::factory()->create(['structure_id' => $this->structure->id, 'user_id' => $moniteur->id]);

    $this->actingAs($this->admin)
        ->get(route('instructors.index'))
        ->assertOk();
});
