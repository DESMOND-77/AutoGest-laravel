<?php

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Models\Document;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Students\Models\RequiredDocumentType;
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

it('shows a hint instead of the deposit form when no required document type is configured', function () {
    $this->seed(RoleSeeder::class);

    $structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $admin = User::factory()->create(['structure_id' => $structure->id]);
    $admin->assignRole('admin');

    $student = Student::factory()->create(['structure_id' => $structure->id]);

    $this->actingAs($admin)->get(route('students.show', $student))
        ->assertOk()
        ->assertSee('Aucune pièce requise n\'est configurée pour cet établissement', false)
        ->assertDontSee('name="required_document_type_id"', false);
});

it('lists a student\'s dossier documents with their review status and a viewer link', function () {
    $this->seed(RoleSeeder::class);

    $structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $admin = User::factory()->create(['structure_id' => $structure->id]);
    $admin->assignRole('admin');

    $student = Student::factory()->create(['structure_id' => $structure->id]);
    $type = RequiredDocumentType::factory()->create(['structure_id' => $structure->id, 'label' => "Carte d'identité"]);
    $document = Document::factory()->create([
        'structure_id' => $structure->id,
        'documentable_type' => Student::class,
        'documentable_id' => $student->id,
        'required_document_type_id' => $type->id,
        'review_status' => DocumentReviewStatus::Approved,
        'is_current' => true,
    ]);

    $this->actingAs($admin)->get(route('students.show', $student))
        ->assertOk()
        ->assertSee("Carte d'identité")
        ->assertSee('Approuvé')
        ->assertSee(route('documents.show', $document), false)
        ->assertSee('name="required_document_type_id"', false);
});
