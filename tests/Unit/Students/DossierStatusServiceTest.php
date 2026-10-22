<?php

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Enums\DocumentType;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Enums\DossierStatus;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\DossierStatusService;
use App\Domain\Tenancy\Models\Structure;

function makeDocument(Student $student, RequiredDocumentType $type, DocumentReviewStatus $status): Document
{
    return Document::factory()->create([
        'structure_id' => $student->structure_id,
        'documentable_type' => $student->getMorphClass(),
        'documentable_id' => $student->id,
        'type' => DocumentType::Other,
        'is_current' => true,
        'required_document_type_id' => $type->id,
        'review_status' => $status,
    ]);
}

it('is incomplete when no required document types exist', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);

    (new DossierStatusService)->syncFor($student);

    expect($student->fresh()->dossier_status)->toBe(DossierStatus::Incomplete);
});

it('is incomplete when a required type has no uploaded document', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);
    RequiredDocumentType::factory()->create(['structure_id' => $structure->id]);

    (new DossierStatusService)->syncFor($student);

    expect($student->fresh()->dossier_status)->toBe(DossierStatus::Incomplete);
});

it('is complete when every required document is uploaded but not all approved', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);
    $type = RequiredDocumentType::factory()->create(['structure_id' => $structure->id]);
    makeDocument($student, $type, DocumentReviewStatus::Pending);

    (new DossierStatusService)->syncFor($student);

    expect($student->fresh()->dossier_status)->toBe(DossierStatus::Complete);
});

it('is validated when every required document is uploaded and approved', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);
    $type = RequiredDocumentType::factory()->create(['structure_id' => $structure->id]);
    makeDocument($student, $type, DocumentReviewStatus::Approved);

    (new DossierStatusService)->syncFor($student);

    expect($student->fresh()->dossier_status)->toBe(DossierStatus::Validated);
});

it('is submitted once document_submitted is true, regardless of document state', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);
    $student->setDocumentSubmitted(true);
    $student->save();

    (new DossierStatusService)->syncFor($student);

    expect($student->fresh()->dossier_status)->toBe(DossierStatus::Submitted);
});

it('drops back to incomplete once a rejected document makes the dossier no longer fully uploaded is false, but stays complete if still all uploaded', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);
    $type = RequiredDocumentType::factory()->create(['structure_id' => $structure->id]);
    makeDocument($student, $type, DocumentReviewStatus::Rejected);

    (new DossierStatusService)->syncFor($student);

    expect($student->fresh()->dossier_status)->toBe(DossierStatus::Complete);
});
