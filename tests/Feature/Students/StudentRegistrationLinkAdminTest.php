<?php

use App\Domain\Students\Services\StudentRegistrationLinkService;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');
});

it('shows the empty state when no link has been generated yet', function () {
    $response = $this->actingAs($this->admin)->get(route('settings.student-registration.show'));

    $response->assertOk();
    $response->assertSee('Aucun lien');
});

it('lets an admin generate a link and reveals the plain token exactly once', function () {
    $generate = $this->actingAs($this->admin)->post(route('settings.student-registration.generate'));
    $generate->assertRedirect(route('settings.student-registration.show'));

    $firstView = $this->actingAs($this->admin)->get(route('settings.student-registration.show'));
    $firstView->assertOk();
    $firstView->assertSee('token=', false);

    $secondView = $this->actingAs($this->admin)->get(route('settings.student-registration.show'));
    $secondView->assertOk();
    $secondView->assertSee('••');
});

it('lets an admin revoke the active link, which immediately stops working', function () {
    $service = app(StudentRegistrationLinkService::class);
    ['token' => $token] = $service->generate($this->structure, $this->admin);

    $this->actingAs($this->admin)->post(route('settings.student-registration.revoke'));

    expect($service->getActiveLink($this->structure))->toBeNull();
    $this->get('/register/student?token='.$token)->assertSee('invalide');
});

it('lets an admin regenerate the link, invalidating the previous token', function () {
    $service = app(StudentRegistrationLinkService::class);
    ['token' => $oldToken] = $service->generate($this->structure, $this->admin);

    $this->actingAs($this->admin)->post(route('settings.student-registration.regenerate'));

    $this->get('/register/student?token='.$oldToken)->assertSee('invalide');

    $newLink = $service->getActiveLink($this->structure);
    expect($newLink)->not->toBeNull();
});

it('denies a non-admin role from viewing the settings page', function () {
    $eleve = User::factory()->create(['structure_id' => $this->structure->id]);
    $eleve->assignRole('eleve');

    $this->actingAs($eleve)->get(route('settings.student-registration.show'))->assertForbidden();
});

it('denies a non-admin role from generating a link', function () {
    $moniteur = User::factory()->create(['structure_id' => $this->structure->id]);
    $moniteur->assignRole('moniteur');

    $this->actingAs($moniteur)->post(route('settings.student-registration.generate'))->assertForbidden();
});

// --- §56: cross-tenant IDOR on the admin management routes ----------------

it('never lets an admin see or manage another tenant\'s link', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $otherAdmin = User::factory()->create(['structure_id' => $otherStructure->id]);
    $otherAdmin->assignRole('admin');

    $service = app(StudentRegistrationLinkService::class);
    ['link' => $otherLink, 'token' => $otherToken] = $service->generate($otherStructure, $otherAdmin);

    // Admin A's settings page never shows admin B's link.
    $response = $this->actingAs($this->admin)->get(route('settings.student-registration.show'));
    $response->assertOk();
    $response->assertDontSee(substr($otherToken, 0, 20));

    // Admin A generating "their" link never touches B's - revoking as A
    // must leave B's link completely untouched.
    $this->actingAs($this->admin)->post(route('settings.student-registration.generate'));
    $this->actingAs($this->admin)->post(route('settings.student-registration.revoke'));

    expect($otherLink->fresh()->isRevoked())->toBeFalse();
    expect($service->getActiveLink($otherStructure)->id)->toBe($otherLink->id);
});

it('scopes the active link shown to each admin\'s own tenant', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $otherAdmin = User::factory()->create(['structure_id' => $otherStructure->id]);
    $otherAdmin->assignRole('admin');

    $this->actingAs($this->admin)->post(route('settings.student-registration.generate'));
    $this->actingAs($otherAdmin)->post(route('settings.student-registration.generate'));

    $service = app(StudentRegistrationLinkService::class);
    $mine = $service->getActiveLink($this->structure);
    $theirs = $service->getActiveLink($otherStructure);

    expect($mine->id)->not->toBe($theirs->id);
    expect($mine->structure_id)->toBe($this->structure->id);
    expect($theirs->structure_id)->toBe($otherStructure->id);
});
