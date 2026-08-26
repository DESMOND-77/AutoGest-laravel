<?php

use App\Domain\Documents\Models\Document;
use App\Domain\Students\Enums\DossierStatus;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\DossierStatusService;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
    $this->type = RequiredDocumentType::factory()->create(['structure_id' => $this->structure->id]);
    $this->student = Student::factory()->stage(LifecycleStage::DossierSetup)->create(['structure_id' => $this->structure->id]);
});

it('moves a dossier from incomplete to complete when an admin uploads the last required document', function () {
    expect($this->student->dossier_status)->toBe(DossierStatus::Incomplete);

    $this->actingAs($this->admin)->post(route('students.documents.store', $this->student), [
        'required_document_type_id' => $this->type->id,
        'file' => UploadedFile::fake()->create('id.pdf', 10),
    ]);

    expect($this->student->fresh()->dossier_status)->toBe(DossierStatus::Complete);
});

it('moves a dossier from complete to validated when the last pending document is approved', function () {
    $this->actingAs($this->admin)->post(route('students.documents.store', $this->student), [
        'required_document_type_id' => $this->type->id,
        'file' => UploadedFile::fake()->create('id.pdf', 10),
    ]);
    expect($this->student->fresh()->dossier_status)->toBe(DossierStatus::Complete);

    $document = Document::query()->where('required_document_type_id', $this->type->id)->firstOrFail();
    $this->actingAs($this->admin)->post(route('documents.approve', $document));

    expect($this->student->fresh()->dossier_status)->toBe(DossierStatus::Validated);
});

it('drops a validated dossier back to complete when its document is rejected', function () {
    $this->actingAs($this->admin)->post(route('students.documents.store', $this->student), [
        'required_document_type_id' => $this->type->id,
        'file' => UploadedFile::fake()->create('id.pdf', 10),
    ]);
    $document = Document::query()->where('required_document_type_id', $this->type->id)->firstOrFail();
    $this->actingAs($this->admin)->post(route('documents.approve', $document));
    expect($this->student->fresh()->dossier_status)->toBe(DossierStatus::Validated);

    $this->actingAs($this->admin)->post(route('documents.reject', $document), ['reason' => 'Illisible']);

    expect($this->student->fresh()->dossier_status)->toBe(DossierStatus::Complete);
});

it('resets document_submitted and drops every tenant student back to incomplete when a new required type is added', function () {
    $this->student->setDocumentSubmitted(true);
    $this->student->setDocumentsZipPath('dossiers/old.zip');
    $this->student->save();
    (new DossierStatusService)->syncFor($this->student);
    expect($this->student->fresh()->dossier_status)->toBe(DossierStatus::Submitted);

    $this->actingAs($this->admin)->post(route('settings.document-types.store'), [
        'label' => 'Nouvelle pièce',
    ]);

    $this->student->refresh();
    expect($this->student->document_submitted)->toBeFalse();
    expect($this->student->documents_zip_path)->toBeNull();
    expect($this->student->dossier_status)->toBe(DossierStatus::Incomplete);
});

it('does not reset document_submitted when an existing required type is merely updated', function () {
    $this->student->setDocumentSubmitted(true);
    $this->student->save();
    (new DossierStatusService)->syncFor($this->student);

    $this->actingAs($this->admin)->patch(route('settings.document-types.update', $this->type), [
        'label' => 'Renommée',
        'is_active' => true,
    ]);

    expect($this->student->fresh()->document_submitted)->toBeTrue();
});
