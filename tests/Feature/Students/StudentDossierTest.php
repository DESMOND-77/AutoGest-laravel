<?php

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\LifecycleService;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    $this->user = User::factory()->create(['structure_id' => $this->structure->id, 'email_verified_at' => now()]);
    $this->user->assignRole('eleve');
    $this->student = Student::factory()->stage(LifecycleStage::DossierSetup)->create([
        'structure_id' => $this->structure->id,
        'user_id' => $this->user->id,
    ]);
    $this->type = RequiredDocumentType::factory()->create(['structure_id' => $this->structure->id]);
});

it('lets an eleve upload a required document', function () {
    $response = $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $this->type),
        ['file' => UploadedFile::fake()->create('id.pdf', 10)],
    );

    $response->assertRedirect();
    $document = Document::query()->where('required_document_type_id', $this->type->id)->firstOrFail();
    expect($document->review_status)->toBe(DocumentReviewStatus::Pending);
    expect($document->documentable_id)->toBe($this->student->id);
});

it('blocks submission until every active required type has a document', function () {
    $this->actingAs($this->user)->post(route('eleve.dossier.submit'))->assertSessionHasErrors('dossier');

    expect($this->student->fresh()->lifecycle_stage)->toBe(LifecycleStage::DossierSetup);
});

it('submits the dossier and transitions to Validation once every piece is present', function () {
    $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $this->type),
        ['file' => UploadedFile::fake()->create('id.pdf', 10)],
    );

    $this->actingAs($this->user)->post(route('eleve.dossier.submit'))->assertRedirect();

    expect($this->student->fresh()->lifecycle_stage)->toBe(LifecycleStage::Validation);
});

it('resets a rejected document to pending and re-links the dossier when re-submitted', function () {
    $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $this->type),
        ['file' => UploadedFile::fake()->create('id.pdf', 10)],
    );
    $this->actingAs($this->user)->post(route('eleve.dossier.submit'));

    $document = Document::query()->where('required_document_type_id', $this->type->id)->where('is_current', true)->firstOrFail();
    $document->update(['review_status' => DocumentReviewStatus::Rejected, 'rejection_reason' => 'Illisible']);
    $this->student->refresh();
    app(LifecycleService::class)->transitionTo($this->student, LifecycleStage::DossierSetup);

    $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $this->type),
        ['file' => UploadedFile::fake()->create('id-v2.pdf', 10)],
    );

    $new = Document::query()->where('required_document_type_id', $this->type->id)->where('is_current', true)->firstOrFail();
    expect($new->review_status)->toBe(DocumentReviewStatus::Pending);
    expect($document->fresh()->is_current)->toBeFalse();
});

it('never lets an eleve upload against another tenant\'s required document type', function () {
    $otherStructure = Structure::factory()->create();
    $otherType = RequiredDocumentType::factory()->create(['structure_id' => $otherStructure->id]);

    $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $otherType),
        ['file' => UploadedFile::fake()->create('id.pdf', 10)],
    )->assertNotFound();
});
