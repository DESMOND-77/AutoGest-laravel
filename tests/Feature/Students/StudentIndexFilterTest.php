<?php

use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
});

it('filters students by search term matching first or last name', function () {
    Student::factory()->create(['structure_id' => $this->structure->id, 'first_name' => 'Sylvie', 'last_name' => 'Mabika']);
    Student::factory()->create(['structure_id' => $this->structure->id, 'first_name' => 'Jean', 'last_name' => 'Ondo']);

    $response = $this->actingAs($this->admin)->get(route('students.index', ['search' => 'Mabika']));

    $response->assertOk();
    $response->assertSee('Sylvie Mabika');
    $response->assertDontSee('Jean Ondo');
});

it('filters students by lifecycle stage', function () {
    Student::factory()->create(['structure_id' => $this->structure->id, 'first_name' => 'Sylvie', 'last_name' => 'Mabika']);
    Student::factory()->stage(LifecycleStage::LicenseObtained)->create(['structure_id' => $this->structure->id, 'first_name' => 'Jean', 'last_name' => 'Ondo']);

    $response = $this->actingAs($this->admin)->get(route('students.index', ['stage' => LifecycleStage::LicenseObtained->value]));

    $response->assertOk();
    $response->assertSee('Jean Ondo');
    $response->assertDontSee('Sylvie Mabika');
});

it('filters students by instructor', function () {
    $instructorA = User::factory()->create(['structure_id' => $this->structure->id]);
    $instructorA->assignRole('moniteur');
    $instructorB = User::factory()->create(['structure_id' => $this->structure->id]);
    $instructorB->assignRole('moniteur');

    Student::factory()->create(['structure_id' => $this->structure->id, 'instructor_id' => $instructorA->id, 'first_name' => 'Sylvie', 'last_name' => 'Mabika']);
    Student::factory()->create(['structure_id' => $this->structure->id, 'instructor_id' => $instructorB->id, 'first_name' => 'Jean', 'last_name' => 'Ondo']);

    $response = $this->actingAs($this->admin)->get(route('students.index', ['instructor_id' => $instructorA->id]));

    $response->assertOk();
    $response->assertSee('Sylvie Mabika');
    $response->assertDontSee('Jean Ondo');
});

it('excludes a deactivated moniteur from the instructor picker', function () {
    $activeInstructor = User::factory()->create(['structure_id' => $this->structure->id]);
    $activeInstructor->assignRole('moniteur');
    $deactivatedInstructor = User::factory()->create(['structure_id' => $this->structure->id, 'is_active' => false]);
    $deactivatedInstructor->assignRole('moniteur');

    $response = $this->actingAs($this->admin)->get(route('students.index'));

    $response->assertOk();
    $instructorIds = $response->viewData('instructors')->pluck('id');
    expect($instructorIds)->toContain($activeInstructor->id);
    expect($instructorIds)->not->toContain($deactivatedInstructor->id);
});
