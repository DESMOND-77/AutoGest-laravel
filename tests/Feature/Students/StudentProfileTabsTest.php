<?php

use App\Domain\Finance\Models\Invoice;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Training\Models\Exam;
use App\Models\User;
use Database\Seeders\RoleSeeder;

it('shows the student profile with formation, planning, payments and exam data', function () {
    $this->seed(RoleSeeder::class);

    $structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $admin = User::factory()->create(['structure_id' => $structure->id]);
    $admin->assignRole('admin');

    $student = Student::factory()->create(['structure_id' => $structure->id, 'first_name' => 'Sylvie', 'last_name' => 'Mabika']);

    LessonSession::factory()->create(['structure_id' => $structure->id, 'student_id' => $student->id]);
    Invoice::factory()->create(['structure_id' => $structure->id, 'student_id' => $student->id, 'label' => 'Forfait complet']);
    Exam::factory()->create(['structure_id' => $structure->id, 'student_id' => $student->id]);

    $response = $this->actingAs($admin)->get(route('students.show', $student));

    $response->assertOk();
    $response->assertSee('Sylvie Mabika');
    $response->assertSee('Vue générale');
    $response->assertSee('Forfait complet');
});
