<?php

use App\Domain\Finance\Enums\InvoiceStatus;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Students\Enums\DossierStatus;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Domain\Training\Enums\SkillLevel;
use App\Domain\Training\Models\Skill;
use App\Domain\Training\Models\SkillProgress;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    $this->eleve = User::factory()->create(['structure_id' => $this->structure->id, 'email_verified_at' => now()]);
    $this->eleve->assignRole('eleve');
    $this->student = Student::factory()->create([
        'structure_id' => $this->structure->id,
        'user_id' => $this->eleve->id,
        'first_name' => 'Awa',
        'last_name' => 'Diallo',
    ]);
});

it('confirms the user-student link is set for a public self-registered eleve', function () {
    expect($this->student->fresh()->user_id)->toBe($this->eleve->id);
});

it('lets an eleve see their own skill progression', function () {
    $skill = Skill::factory()->create(['structure_id' => $this->structure->id, 'label' => 'Créneau']);
    SkillProgress::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->student->id,
        'skill_id' => $skill->id,
        'level' => SkillLevel::Acquired,
    ]);

    $this->actingAs($this->eleve)->get(route('eleve.progression'))
        ->assertOk()
        ->assertSee('Créneau')
        ->assertSee('Acquis');
});

it('does not let an eleve see another student\'s progression', function () {
    $otherStudent = Student::factory()->create(['structure_id' => $this->structure->id]);
    $skill = Skill::factory()->create(['structure_id' => $this->structure->id, 'label' => 'Autre compétence']);
    SkillProgress::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $otherStudent->id,
        'skill_id' => $skill->id,
        'level' => SkillLevel::Acquired,
    ]);

    $this->actingAs($this->eleve)->get(route('eleve.progression'))
        ->assertOk()
        ->assertDontSee('Acquis');
});

it('lets an eleve see their own invoices and balance due', function () {
    $invoice = Invoice::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $this->student->id,
        'label' => 'Forfait Code + Conduite',
        'amount_due' => 200000,
        'amount_paid' => 50000,
        'status' => InvoiceStatus::Partial,
    ]);

    $this->actingAs($this->eleve)->get(route('eleve.paiements'))
        ->assertOk()
        ->assertSee('Forfait Code + Conduite')
        ->assertSee('150');
});

it('does not let an eleve see another student\'s invoices', function () {
    $otherStudent = Student::factory()->create(['structure_id' => $this->structure->id]);
    Invoice::factory()->create([
        'structure_id' => $this->structure->id,
        'student_id' => $otherStudent->id,
        'label' => 'Facture confidentielle',
    ]);

    $this->actingAs($this->eleve)->get(route('eleve.paiements'))
        ->assertOk()
        ->assertDontSee('Facture confidentielle');
});

it('shows the eleve their dossier status alongside their documents', function () {
    $this->actingAs($this->eleve)->get(route('eleve.dossier.show'))
        ->assertOk()
        ->assertSee(DossierStatus::Incomplete->label());
});

it('denies a moniteur access to the eleve self-service screens', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id, 'email_verified_at' => now()]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->get(route('eleve.progression'))->assertForbidden();
    $this->actingAs($moniteur)->get(route('eleve.paiements'))->assertForbidden();
});

it('denies an admin access to the eleve self-service screens', function () {
    $admin = User::factory()->create(['structure_id' => $this->structure->id, 'email_verified_at' => now()]);
    $admin->assignRole('admin');

    $this->actingAs($admin)->get(route('eleve.progression'))->assertForbidden();
    $this->actingAs($admin)->get(route('eleve.paiements'))->assertForbidden();
});
