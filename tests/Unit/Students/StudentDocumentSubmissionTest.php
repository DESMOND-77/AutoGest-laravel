<?php

use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;

it('persists document_submitted through the bypass setter with the boolean cast round-tripping', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);

    $student->setDocumentSubmitted(true);
    $student->save();

    expect($student->fresh()->document_submitted)->toBeTrue();
});

it('persists documents_zip_path through the bypass setter', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);

    $student->setDocumentsZipPath('some/path.zip');
    $student->save();

    expect($student->fresh()->documents_zip_path)->toBe('some/path.zip');
});

it('clears documents_zip_path back to null through the bypass setter', function () {
    $structure = Structure::factory()->create();
    $student = Student::factory()->create(['structure_id' => $structure->id]);

    $student->setDocumentsZipPath('some/path.zip');
    $student->save();

    $student->setDocumentsZipPath(null);
    $student->save();

    expect($student->fresh()->documents_zip_path)->toBeNull();
});

it('guards document_submitted and documents_zip_path against mass assignment', function () {
    expect((new Student)->getFillable())->not->toContain('document_submitted', 'documents_zip_path');
});
