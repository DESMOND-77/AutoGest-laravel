<?php

use App\Domain\Finance\Enums\InvoiceStatus;
use App\Domain\Finance\Enums\LedgerEntryType;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\LedgerEntry;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');

    LedgerEntry::factory()->create([
        'structure_id' => $this->structure->id,
        'type' => LedgerEntryType::Income,
        'amount' => 42000,
        'occurred_on' => now()->toDateString(),
    ]);
});

it('renders the admin dashboard with revenue, exam and fleet stats', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Recettes des 6 derniers mois')
        ->assertSee('42 000 FCFA');
});

it('shows cash balance, outstanding balance, today\'s sessions and recent ledger entries', function () {
    $student = Student::factory()->create(['structure_id' => $this->structure->id]);
    $instructor = User::factory()->create(['structure_id' => $this->structure->id]);

    Invoice::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $student->id,
        'status' => InvoiceStatus::Unpaid, 'amount_due' => 100000, 'amount_paid' => 0,
    ]);
    LessonSession::factory()->create([
        'structure_id' => $this->structure->id, 'student_id' => $student->id, 'instructor_id' => $instructor->id,
        'scheduled_date' => now()->toDateString(),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Solde caisse')
        ->assertSee('Reste à collecter')
        ->assertSee('100 000 FCFA')
        ->assertSee('Séances aujourd\'hui', false)
        ->assertSee($student->fullName())
        ->assertSee('Dernières opérations financières')
        ->assertSee('Recette (caisse)');
});

it('does not leak another tenant\'s sessions or ledger entries onto the dashboard', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $otherStudent = Student::factory()->create(['structure_id' => $otherStructure->id, 'first_name' => 'Autre', 'last_name' => 'Tenant']);
    $otherInstructor = User::factory()->create(['structure_id' => $otherStructure->id]);

    LessonSession::factory()->create([
        'structure_id' => $otherStructure->id, 'student_id' => $otherStudent->id, 'instructor_id' => $otherInstructor->id,
        'scheduled_date' => now()->toDateString(),
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Autre Tenant');
});

it('exports the revenue CSV scoped to the admin\'s own tenant', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    LedgerEntry::factory()->create([
        'structure_id' => $otherStructure->id,
        'type' => LedgerEntryType::Income,
        'amount' => 999999,
        'occurred_on' => now()->toDateString(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('reports.revenue.csv'));

    $response->assertOk();
    $response->assertHeader('content-disposition', 'attachment; filename=recettes-mensuelles.csv');

    $content = $response->streamedContent();
    expect($content)->toContain('42000');
    expect($content)->not->toContain('999999');
});
