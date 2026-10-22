<?php

use App\Domain\Finance\Models\Invoice;
use App\Domain\Notifications\Channels\SmsChannel;
use App\Domain\Notifications\Contracts\SmsGateway;
use App\Domain\Notifications\Notifications\SmsNotification;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Notification;

it('sends through the bound SmsGateway when routed to a phone number', function () {
    $gateway = Mockery::mock(SmsGateway::class);
    $gateway->shouldReceive('send')->once()->with('074123456', 'Bonjour');
    $this->app->instance(SmsGateway::class, $gateway);

    app(SmsChannel::class)->send(
        Notification::route('sms', '074123456'),
        new SmsNotification('Bonjour'),
    );
});

it('does nothing when there is no phone route', function () {
    $gateway = Mockery::mock(SmsGateway::class);
    $gateway->shouldNotReceive('send');
    $this->app->instance(SmsGateway::class, $gateway);

    app(SmsChannel::class)->send(
        Notification::route('mail', 'someone@example.com'),
        new SmsNotification('Bonjour'),
    );
});

it('texts the student when a payment is recorded, if a phone number is on file', function () {
    Notification::fake();
    $this->seed(RoleSeeder::class);

    $structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $admin = User::factory()->create(['structure_id' => $structure->id]);
    $admin->assignRole('admin');

    $student = Student::factory()->create(['structure_id' => $structure->id, 'phone' => '074123456']);
    $invoice = Invoice::factory()->create([
        'structure_id' => $structure->id,
        'student_id' => $student->id,
        'amount_due' => 100000,
        'amount_paid' => 0,
    ]);

    $this->actingAs($admin)->post(route('finance.invoices.payments.store', $invoice), [
        'amount' => 50000,
        'method' => 'cash',
    ]);

    Notification::assertSentOnDemand(
        SmsNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['sms'] === '074123456'
            && str_contains($notification->message, '50 000 FCFA'),
    );
});

it('does not attempt an SMS when the student has no phone number on file', function () {
    Notification::fake();
    $this->seed(RoleSeeder::class);

    $structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $admin = User::factory()->create(['structure_id' => $structure->id]);
    $admin->assignRole('admin');

    $student = Student::factory()->create(['structure_id' => $structure->id, 'phone' => null]);
    $invoice = Invoice::factory()->create([
        'structure_id' => $structure->id,
        'student_id' => $student->id,
        'amount_due' => 100000,
        'amount_paid' => 0,
    ]);

    $this->actingAs($admin)->post(route('finance.invoices.payments.store', $invoice), [
        'amount' => 50000,
        'method' => 'cash',
    ]);

    // The admin AlertNotification still legitimately fires - only the SMS
    // (which needs a phone number to route to) should be skipped.
    Notification::assertSentOnDemandTimes(SmsNotification::class, 0);
});
