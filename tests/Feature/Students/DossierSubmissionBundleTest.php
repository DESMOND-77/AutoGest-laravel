<?php

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Enums\DossierStatus;
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
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
    $this->eleve = User::factory()->create(['structure_id' => $this->structure->id, 'email_verified_at' => now()]);
    $this->eleve->assignRole('eleve');
    $this->moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->moniteur->assignRole('moniteur');
    $this->student = Student::factory()->stage(LifecycleStage::DossierSetup)->create([
        'structure_id' => $this->structure->id,
        'user_id' => $this->eleve->id,
        'instructor_id' => $this->moniteur->id,
    ]);
    $this->type = RequiredDocumentType::factory()->create(['structure_id' => $this->structure->id]);
});

it('bundles documents into a zip and flips document_submitted on successful submission', function () {
    $this->actingAs($this->eleve)->post(route('eleve.dossier.upload', $this->type), [
        'file' => UploadedFile::fake()->create('id.pdf', 10),
    ]);
    Document::query()->where('required_document_type_id', $this->type->id)->firstOrFail()
        ->update(['review_status' => DocumentReviewStatus::Approved]);

    $this->actingAs($this->eleve)->post(route('eleve.dossier.submit'))->assertRedirect(route('eleve.dossier.show'));

    $this->student->refresh();
    expect($this->student->document_submitted)->toBeTrue();
    expect($this->student->documents_zip_path)->not->toBeNull();
    expect($this->student->dossier_status)->toBe(DossierStatus::Submitted);
    Storage::disk('local')->assertExists($this->student->documents_zip_path);
});

it('does not bundle or flip document_submitted when submission is blocked', function () {
    $this->actingAs($this->eleve)->post(route('eleve.dossier.submit'))->assertSessionHasErrors('dossier');

    expect($this->student->fresh()->document_submitted)->toBeFalse();
    expect($this->student->fresh()->documents_zip_path)->toBeNull();
});

it('lets an admin download the submitted dossier bundle', function () {
    $this->actingAs($this->eleve)->post(route('eleve.dossier.upload', $this->type), [
        'file' => UploadedFile::fake()->create('id.pdf', 10),
    ]);
    Document::query()->where('required_document_type_id', $this->type->id)->firstOrFail()
        ->update(['review_status' => DocumentReviewStatus::Approved]);
    $this->actingAs($this->eleve)->post(route('eleve.dossier.submit'));

    $this->actingAs($this->admin)
        ->get(route('students.dossier-download', $this->student))
        ->assertOk();
});

it('forbids a moniteur assigned to the student from downloading the dossier bundle', function () {
    $this->actingAs($this->eleve)->post(route('eleve.dossier.upload', $this->type), [
        'file' => UploadedFile::fake()->create('id.pdf', 10),
    ]);
    Document::query()->where('required_document_type_id', $this->type->id)->firstOrFail()
        ->update(['review_status' => DocumentReviewStatus::Approved]);
    $this->actingAs($this->eleve)->post(route('eleve.dossier.submit'));

    $this->actingAs($this->moniteur)
        ->get(route('students.dossier-download', $this->student))
        ->assertForbidden();
});

it('404s the download route when no bundle exists yet', function () {
    $this->actingAs($this->admin)
        ->get(route('students.dossier-download', $this->student))
        ->assertNotFound();
});

it('does not let an admin of another tenant download the bundle', function () {
    $this->actingAs($this->eleve)->post(route('eleve.dossier.upload', $this->type), [
        'file' => UploadedFile::fake()->create('id.pdf', 10),
    ]);
    Document::query()->where('required_document_type_id', $this->type->id)->firstOrFail()
        ->update(['review_status' => DocumentReviewStatus::Approved]);
    $this->actingAs($this->eleve)->post(route('eleve.dossier.submit'));

    $otherStructure = Structure::factory()->create();
    $otherAdmin = User::factory()->create(['structure_id' => $otherStructure->id]);
    $otherAdmin->assignRole('admin');

    $this->actingAs($otherAdmin)
        ->get(route('students.dossier-download', $this->student))
        ->assertNotFound();
});
