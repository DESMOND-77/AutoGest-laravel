<?php

use App\Domain\Documents\Models\Document;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');

    $this->structure = Structure::factory()->create();
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');

    $this->student = Student::factory()->create(['structure_id' => $this->structure->id]);
    $this->document = Document::factory()->create([
        'structure_id' => $this->structure->id,
        'documentable_type' => Student::class,
        'documentable_id' => $this->student->id,
        'original_name' => 'carte-identite.pdf',
    ]);

    Storage::disk($this->document->disk)->put($this->document->path, 'fake pdf contents');
});

it('lets an admin open the viewer page for a document they can see', function () {
    $this->actingAs($this->admin)->get(route('documents.show', $this->document))
        ->assertOk()
        ->assertSee('carte-identite.pdf')
        ->assertSee(route('documents.stream', $this->document), false)
        ->assertSee(route('documents.download', $this->document), false);
});

it('streams the file inline instead of forcing a download', function () {
    $response = $this->actingAs($this->admin)->get(route('documents.stream', $this->document));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('inline');
});

it('denies a non-admin, non-moniteur role from viewing the document', function () {
    $eleve = User::factory()->create(['structure_id' => $this->structure->id]);
    $eleve->assignRole('eleve');

    $this->actingAs($eleve)->get(route('documents.show', $this->document))->assertForbidden();
});

it('never lets an admin view another tenant\'s document', function () {
    $otherStructure = Structure::factory()->create();
    $otherAdmin = User::factory()->create(['structure_id' => $otherStructure->id]);
    $otherAdmin->assignRole('admin');

    $this->actingAs($otherAdmin)->get(route('documents.show', $this->document))->assertNotFound();
});
