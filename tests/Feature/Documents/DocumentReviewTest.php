<?php

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');

    $this->type = RequiredDocumentType::factory()->create(['structure_id' => $this->structure->id]);
    $this->student = Student::factory()->stage(LifecycleStage::Validation)->create(['structure_id' => $this->structure->id]);
    $this->document = Document::factory()->create([
        'structure_id' => $this->structure->id,
        'documentable_type' => Student::class,
        'documentable_id' => $this->student->id,
        'required_document_type_id' => $this->type->id,
        'review_status' => DocumentReviewStatus::Pending,
        'is_current' => true,
    ]);
});

it('sends the student back to dossier setup when the only document is rejected', function () {
    $this->actingAs($this->admin)->post(route('documents.reject', $this->document), ['reason' => 'Illisible']);

    expect($this->document->fresh()->review_status)->toBe(DocumentReviewStatus::Rejected);
    expect($this->document->fresh()->rejection_reason)->toBe('Illisible');
    expect($this->student->fresh()->lifecycle_stage)->toBe(LifecycleStage::DossierSetup);
});

it('advances the student to enrollment once every active required document is approved', function () {
    $this->actingAs($this->admin)->post(route('documents.approve', $this->document));

    expect($this->document->fresh()->review_status)->toBe(DocumentReviewStatus::Approved);
    expect($this->student->fresh()->lifecycle_stage)->toBe(LifecycleStage::Enrollment);
});

it('does not advance the student while another required document is still pending', function () {
    $secondType = RequiredDocumentType::factory()->create(['structure_id' => $this->structure->id]);
    Document::factory()->create([
        'structure_id' => $this->structure->id,
        'documentable_type' => Student::class,
        'documentable_id' => $this->student->id,
        'required_document_type_id' => $secondType->id,
        'review_status' => DocumentReviewStatus::Pending,
        'is_current' => true,
    ]);

    $this->actingAs($this->admin)->post(route('documents.approve', $this->document));

    expect($this->student->fresh()->lifecycle_stage)->toBe(LifecycleStage::Validation);
});

it('refuses a review action when the student is no longer at the Validation stage', function () {
    $this->student->setLifecycleStage(LifecycleStage::Enrollment);
    $this->student->save();

    $this->actingAs($this->admin)->post(route('documents.approve', $this->document))->assertForbidden();

    expect($this->document->fresh()->review_status)->toBe(DocumentReviewStatus::Pending);
});

it('never lets an admin review another tenant\'s document', function () {
    $otherStructure = Structure::factory()->create();
    $otherAdmin = User::factory()->create(['structure_id' => $otherStructure->id]);
    $otherAdmin->assignRole('admin');

    // Document is BelongsToTenant-scoped, so the route-model-bound lookup
    // never matches a row for another tenant's admin — a 404, not a 403,
    // which is the stronger guarantee (no existence leak). See
    // App\Support\BelongsToTenant.
    $this->actingAs($otherAdmin)->post(route('documents.approve', $this->document))->assertNotFound();

    expect($this->document->fresh()->review_status)->toBe(DocumentReviewStatus::Pending);
});

it('denies a non-admin role from reviewing documents', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->post(route('documents.approve', $this->document))->assertForbidden();
});
