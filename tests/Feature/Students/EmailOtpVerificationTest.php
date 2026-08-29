<?php

use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\EmailOtpService;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->structure = Structure::factory()->create();
    $this->user = User::factory()->create(['structure_id' => $this->structure->id, 'email_verified_at' => null]);
    $this->user->assignRole('eleve');
    $this->student = Student::factory()->create(['structure_id' => $this->structure->id, 'user_id' => $this->user->id]);
});

it('blocks an unverified eleve from a gated route and sends them to the OTP screen', function () {
    $response = $this->actingAs($this->user)->get(route('eleve.dashboard'));

    $response->assertRedirect(route('eleve.otp.show'));
});

it('lets a verified eleve reach gated routes normally', function () {
    $this->user->forceFill(['email_verified_at' => now()])->save();

    $this->actingAs($this->user)->get(route('eleve.dashboard'))->assertOk();
});

it('verifies a correct code and advances the student straight to dossier setup', function () {
    Mail::fake();
    $code = app(EmailOtpService::class)->generate($this->user);

    $response = $this->actingAs($this->user)->post(route('eleve.otp.verify'), ['code' => $code]);

    $response->assertRedirect(route('eleve.dashboard'));
    expect($this->user->fresh()->email_verified_at)->not->toBeNull();
    expect($this->student->fresh()->lifecycle_stage)->toBe(LifecycleStage::DossierSetup);
});

it('rejects a wrong code with a field error and does not verify the account', function () {
    Mail::fake();
    app(EmailOtpService::class)->generate($this->user);

    $response = $this->actingAs($this->user)->post(route('eleve.otp.verify'), ['code' => '000000']);

    $response->assertSessionHasErrors('code');
    expect($this->user->fresh()->email_verified_at)->toBeNull();
});

it('lets the user resend a code, throttled to once per minute', function () {
    Mail::fake();

    $this->actingAs($this->user)->post(route('eleve.otp.resend'))->assertRedirect();
    $response = $this->actingAs($this->user)->post(route('eleve.otp.resend'));

    $response->assertStatus(429);
});

it('locks out after 5 wrong attempts even with the eventual right code', function () {
    Mail::fake();
    $code = app(EmailOtpService::class)->generate($this->user);

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($this->user)->post(route('eleve.otp.verify'), ['code' => '000000']);
    }

    $response = $this->actingAs($this->user)->post(route('eleve.otp.verify'), ['code' => $code]);

    $response->assertSessionHasErrors('code');
    expect($this->user->fresh()->email_verified_at)->toBeNull();
});
