<?php

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
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

it('renders the dossier screen when no document has been uploaded yet', function () {
    $this->actingAs($this->user)->get(route('eleve.dossier.show'))
        ->assertOk()
        ->assertSee($this->type->label);
});

it('renders the dossier screen with the submit button enabled once every piece is approved', function () {
    $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $this->type),
        ['file' => UploadedFile::fake()->create('id.pdf', 10)],
    );

    Document::query()->where('required_document_type_id', $this->type->id)->firstOrFail()
        ->update(['review_status' => DocumentReviewStatus::Approved]);

    $this->actingAs($this->user)->get(route('eleve.dossier.show'))->assertOk();
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

it('blocks submission while a required document is uploaded but not yet approved', function () {
    $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $this->type),
        ['file' => UploadedFile::fake()->create('id.pdf', 10)],
    );

    $this->actingAs($this->user)->post(route('eleve.dossier.submit'))->assertSessionHasErrors('dossier');

    expect($this->student->fresh()->lifecycle_stage)->toBe(LifecycleStage::DossierSetup);
});

it('blocks submission while any required document remains pending or rejected, even if others are approved', function () {
    $secondType = RequiredDocumentType::factory()->create(['structure_id' => $this->structure->id]);

    $this->actingAs($this->user)->post(route('eleve.dossier.upload', $this->type), ['file' => UploadedFile::fake()->create('id.pdf', 10)]);
    $this->actingAs($this->user)->post(route('eleve.dossier.upload', $secondType), ['file' => UploadedFile::fake()->create('id2.pdf', 10)]);

    Document::query()->where('required_document_type_id', $this->type->id)->firstOrFail()
        ->update(['review_status' => DocumentReviewStatus::Approved]);
    // The second type's document is left at its default Pending status.

    $this->actingAs($this->user)->post(route('eleve.dossier.submit'))->assertSessionHasErrors('dossier');

    expect($this->student->fresh()->lifecycle_stage)->toBe(LifecycleStage::DossierSetup);
});

it('submits the dossier and transitions straight through to Enrollment once every piece is approved', function () {
    $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $this->type),
        ['file' => UploadedFile::fake()->create('id.pdf', 10)],
    );

    Document::query()->where('required_document_type_id', $this->type->id)->firstOrFail()
        ->update(['review_status' => DocumentReviewStatus::Approved]);

    $this->actingAs($this->user)->post(route('eleve.dossier.submit'))->assertRedirect();

    expect($this->student->fresh()->lifecycle_stage)->toBe(LifecycleStage::Enrollment);
});

it('resets a rejected document to pending when re-uploaded, and still requires re-approval before submission', function () {
    $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $this->type),
        ['file' => UploadedFile::fake()->create('id.pdf', 10)],
    );

    $document = Document::query()->where('required_document_type_id', $this->type->id)->where('is_current', true)->firstOrFail();
    $document->update(['review_status' => DocumentReviewStatus::Rejected, 'rejection_reason' => 'Illisible']);

    $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $this->type),
        ['file' => UploadedFile::fake()->create('id-v2.pdf', 10)],
    );

    $new = Document::query()->where('required_document_type_id', $this->type->id)->where('is_current', true)->firstOrFail();
    expect($new->review_status)->toBe(DocumentReviewStatus::Pending);
    expect($document->fresh()->is_current)->toBeFalse();

    $this->actingAs($this->user)->post(route('eleve.dossier.submit'))->assertSessionHasErrors('dossier');
    expect($this->student->fresh()->lifecycle_stage)->toBe(LifecycleStage::DossierSetup);
});

it('rejects an oversized file with an explicit French error', function () {
    $response = $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $this->type),
        ['file' => UploadedFile::fake()->create('id.pdf', 6000)],
    );

    $response->assertSessionHasErrors(['file' => 'Ce fichier est trop volumineux (5 Mo maximum).']);
    expect(Document::query()->where('required_document_type_id', $this->type->id)->exists())->toBeFalse();
});

it('rejects a disallowed file extension with an explicit French error', function () {
    $response = $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $this->type),
        ['file' => UploadedFile::fake()->create('malware.exe', 10)],
    );

    $response->assertSessionHasErrors(['file' => 'Format de fichier non autorisé. Formats acceptés : PDF, JPG, PNG ou WEBP.']);
    expect(Document::query()->where('required_document_type_id', $this->type->id)->exists())->toBeFalse();
});

it('rejects an empty file with an explicit French error', function () {
    $response = $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $this->type),
        ['file' => UploadedFile::fake()->create('empty.pdf', 0)],
    );

    $response->assertSessionHasErrors(['file' => 'Ce fichier semble vide ou corrompu.']);
    expect(Document::query()->where('required_document_type_id', $this->type->id)->exists())->toBeFalse();
});

it('shows the file validation error banner on the dossier screen after a rejected upload', function () {
    $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $this->type),
        ['file' => UploadedFile::fake()->create('malware.exe', 10)],
    );

    $this->actingAs($this->user)->get(route('eleve.dossier.show'))
        ->assertSee('Format de fichier non autorisé. Formats acceptés : PDF, JPG, PNG ou WEBP.');
});

it('never lets an eleve upload against another tenant\'s required document type', function () {
    $otherStructure = Structure::factory()->create();
    $otherType = RequiredDocumentType::factory()->create(['structure_id' => $otherStructure->id]);

    $this->actingAs($this->user)->post(
        route('eleve.dossier.upload', $otherType),
        ['file' => UploadedFile::fake()->create('id.pdf', 10)],
    )->assertNotFound();
});
