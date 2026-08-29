<?php

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Enums\DocumentType;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Services\DocumentService;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');

    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');

    $this->student = Student::factory()->create(['structure_id' => $this->structure->id]);
});

it('uploads a document for a student and supersedes the previous version on re-upload', function () {
    $this->actingAs($this->admin)->post(route('students.documents.store', $this->student), [
        'type' => DocumentType::IdCard->value,
        'file' => UploadedFile::fake()->create('cni.pdf', 100, 'application/pdf'),
    ])->assertRedirect();

    $first = Document::query()->where('documentable_id', $this->student->id)->sole();
    expect($first->version)->toBe(1);
    expect($first->is_current)->toBeTrue();

    $this->actingAs($this->admin)->post(route('students.documents.store', $this->student), [
        'type' => DocumentType::IdCard->value,
        'file' => UploadedFile::fake()->create('cni-v2.pdf', 100, 'application/pdf'),
    ])->assertRedirect();

    expect($first->fresh()->is_current)->toBeFalse();

    $current = Document::query()->where('documentable_id', $this->student->id)->where('is_current', true)->sole();
    expect($current->version)->toBe(2);

    Storage::disk('local')->assertExists($first->path);
    Storage::disk('local')->assertExists($current->path);
});

it('lets the owning admin download the document', function () {
    $document = Document::factory()->create([
        'structure_id' => $this->structure->id,
        'documentable_id' => $this->student->id,
    ]);
    Storage::disk('local')->put($document->path, 'fake-content');

    $this->actingAs($this->admin)
        ->get(route('documents.download', $document))
        ->assertOk()
        ->assertStreamedContent('fake-content');
});

it('does not let an admin of another school download the document', function () {
    $document = Document::factory()->create([
        'structure_id' => $this->structure->id,
        'documentable_id' => $this->student->id,
    ]);
    Storage::disk('local')->put($document->path, 'fake-content');

    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $otherAdmin = User::factory()->create(['structure_id' => $otherStructure->id]);
    $otherAdmin->assignRole('admin');

    $this->actingAs($otherAdmin)
        ->get(route('documents.download', $document))
        ->assertNotFound();
});

it('versions dossier documents by required_document_type_id, not by DocumentType, and resets review status', function () {
    $structure = Structure::factory()->create();
    TenantContext::set($structure);
    $student = Student::factory()->create(['structure_id' => $structure->id]);
    $requiredType = RequiredDocumentType::factory()->create(['structure_id' => $structure->id]);

    $service = app(DocumentService::class);

    $first = $service->upload(
        UploadedFile::fake()->create('id-card.pdf', 10),
        $student,
        DocumentType::Other,
        null,
        null,
        $requiredType,
    );

    expect($first->review_status)->toBe(DocumentReviewStatus::Pending);

    $first->update(['review_status' => DocumentReviewStatus::Approved, 'reviewed_at' => now()]);

    $second = $service->upload(
        UploadedFile::fake()->create('id-card-v2.pdf', 10),
        $student,
        DocumentType::Other,
        null,
        null,
        $requiredType,
    );

    expect($first->fresh()->is_current)->toBeFalse();
    expect($second->is_current)->toBeTrue();
    expect($second->version)->toBe(2);
    expect($second->review_status)->toBe(DocumentReviewStatus::Pending);
    expect($second->required_document_type_id)->toBe($requiredType->id);
});
