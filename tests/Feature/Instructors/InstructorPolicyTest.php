<?php

use App\Domain\Instructors\Models\Instructor;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->schoolA = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->schoolB = Structure::factory()->create(['status' => StructureStatus::Active]);

    $this->adminB = User::factory()->create(['structure_id' => $this->schoolB->id]);
    $this->adminB->assignRole('admin');

    $userA = User::factory()->create(['structure_id' => $this->schoolA->id]);
    $userA->assignRole('moniteur');
    $this->instructorA = Instructor::factory()->create([
        'structure_id' => $this->schoolA->id,
        'user_id' => $userA->id,
    ]);

    $otherUserA = User::factory()->create(['structure_id' => $this->schoolA->id]);
    $otherUserA->assignRole('moniteur');
    $this->otherInstructorA = Instructor::factory()->create([
        'structure_id' => $this->schoolA->id,
        'user_id' => $otherUserA->id,
    ]);
});

it('does not let an admin of school B view an instructor belonging to school A', function () {
    $this->actingAs($this->adminB)
        ->get(route('instructors.show', $this->instructorA))
        ->assertForbidden();
});

it('does not let an admin of school B delete an instructor belonging to school A', function () {
    $this->actingAs($this->adminB)
        ->delete(route('instructors.destroy', $this->instructorA))
        ->assertForbidden();

    expect(Instructor::withoutGlobalScopes()->find($this->instructorA->id))->not->toBeNull();
});

it('does not let a moniteur manage availabilities of another moniteur in the same school', function () {
    $this->actingAs($this->instructorA->user)
        ->post(route('instructors.availabilities.store', $this->otherInstructorA), [
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '12:00',
        ])
        ->assertForbidden();
});

it('lets a moniteur manage their own availabilities', function () {
    $this->actingAs($this->instructorA->user)
        ->post(route('instructors.availabilities.store', $this->instructorA), [
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '12:00',
        ])
        ->assertRedirect();

    expect($this->instructorA->availabilities()->count())->toBe(1);
});
