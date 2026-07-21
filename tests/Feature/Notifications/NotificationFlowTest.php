<?php

use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Services\PaymentService;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use App\Support\TenantContext;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');

    TenantContext::set($this->structure);
    $student = Student::factory()->create(['structure_id' => $this->structure->id]);
    $this->invoice = Invoice::factory()->create(['structure_id' => $this->structure->id, 'student_id' => $student->id]);
});

afterEach(fn () => TenantContext::clear());

it('notifies every admin of the tenant when a payment is recorded', function () {
    app(PaymentService::class)->record($this->invoice, ['amount' => 5000, 'method' => 'cash']);

    expect($this->admin->fresh()->unreadNotifications()->count())->toBe(1);
    expect($this->admin->fresh()->notifications()->first()->data['title'])->toBe('Paiement reçu');
});

/**
 * Regression coverage for the legacy api/notifications.php GET-with-side-effect
 * bug flagged in the architecture audit: loading the bell dropdown silently
 * discarded which notifications were unread. Here GET is read-only.
 */
it('does not mark notifications as read on a plain GET', function () {
    app(PaymentService::class)->record($this->invoice, ['amount' => 5000, 'method' => 'cash']);

    $this->actingAs($this->admin)->getJson(route('notifications.index'))->assertOk();

    expect($this->admin->fresh()->unreadNotifications()->count())->toBe(1);
});

it('marks notifications as read only via the explicit POST endpoint', function () {
    app(PaymentService::class)->record($this->invoice, ['amount' => 5000, 'method' => 'cash']);

    $this->actingAs($this->admin)->post(route('notifications.read'))->assertRedirect();

    expect($this->admin->fresh()->unreadNotifications()->count())->toBe(0);
});
