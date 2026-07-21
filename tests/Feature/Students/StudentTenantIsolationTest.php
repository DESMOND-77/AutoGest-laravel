<?php

use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

/**
 * Regression coverage for the two IDOR bugs found in the legacy PHP app:
 * admin/eleves.php allowed editing/deleting a student from another tenant by
 * guessing the id, and moniteur/evaluation.php let a moniteur read/write an
 * evaluation for a student that wasn't theirs. BelongsToTenant + StudentPolicy
 * are what's supposed to make both impossible now.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->schoolA = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->schoolB = Structure::factory()->create(['status' => StructureStatus::Active]);

    $this->adminA = User::factory()->create(['structure_id' => $this->schoolA->id]);
    $this->adminA->assignRole('admin');

    $this->adminB = User::factory()->create(['structure_id' => $this->schoolB->id]);
    $this->adminB->assignRole('admin');

    $this->studentA = Student::factory()->create([
        'structure_id' => $this->schoolA->id,
        'last_name' => 'Mabika',
        'first_name' => 'Sylvie',
    ]);
});

it('does not let an admin of school B edit a student belonging to school A', function () {
    $this->actingAs($this->adminB)
        ->put(route('students.update', $this->studentA), [
            'last_name' => 'Hacked',
            'first_name' => 'Sylvie',
            'license_category' => 'B',
            'course_type' => 'normal',
        ])
        ->assertForbidden();

    expect($this->studentA->fresh()->last_name)->toBe('Mabika');
});

it('does not let an admin of school B delete a student belonging to school A', function () {
    $this->actingAs($this->adminB)
        ->delete(route('students.destroy', $this->studentA))
        ->assertForbidden();

    expect(Student::withoutGlobalScopes()->find($this->studentA->id))->not->toBeNull();
});

it('does not let an admin of school B view a student belonging to school A', function () {
    $this->actingAs($this->adminB)
        ->get(route('students.show', $this->studentA))
        ->assertForbidden();
});

it('scopes the student index to the current tenant', function () {
    Student::factory()->create(['structure_id' => $this->schoolB->id, 'last_name' => 'Autre']);

    $response = $this->actingAs($this->adminA)->get(route('students.index'));

    $response->assertOk();
    $response->assertSee('Mabika');
    $response->assertDontSee('Autre');
});

it('lets a moniteur view only students assigned to them', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->schoolA->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)
        ->get(route('students.show', $this->studentA))
        ->assertForbidden();

    $this->studentA->update(['instructor_id' => $moniteur->id]);

    $this->actingAs($moniteur)
        ->get(route('students.show', $this->studentA))
        ->assertOk();
});
