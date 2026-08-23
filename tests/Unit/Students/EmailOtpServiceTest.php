<?php

use App\Domain\Students\Exceptions\InvalidOtp;
use App\Domain\Students\Mail\EmailOtpMail;
use App\Domain\Students\Models\EmailOtp;
use App\Domain\Students\Services\EmailOtpService;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->service = app(EmailOtpService::class);
    $this->user = User::factory()->create();
});

it('generates a 6-digit code, stores only its hash, and emails it', function () {
    $code = $this->service->generate($this->user);

    expect($code)->toMatch('/^\d{6}$/');

    $otp = EmailOtp::query()->where('user_id', $this->user->id)->firstOrFail();
    expect($otp->code_hash)->toBe(hash('sha256', $code));
    expect($otp->consumed_at)->toBeNull();
    expect($otp->attempts)->toBe(0);

    Mail::assertSent(EmailOtpMail::class);
});

it('replaces any previous code on resend instead of stacking a second row', function () {
    $first = $this->service->generate($this->user);
    $second = $this->service->resend($this->user);

    expect($second)->not->toBe($first);
    expect(EmailOtp::query()->where('user_id', $this->user->id)->count())->toBe(1);

    // The old code no longer verifies.
    expect(fn () => $this->service->verify($this->user, $first))->toThrow(InvalidOtp::class);
});

it('verifies a correct code and consumes it', function () {
    $code = $this->service->generate($this->user);

    $this->service->verify($this->user, $code);

    expect(EmailOtp::query()->where('user_id', $this->user->id)->first()->consumed_at)->not->toBeNull();
});

it('rejects a wrong code and increments the attempt counter', function () {
    $this->service->generate($this->user);

    expect(fn () => $this->service->verify($this->user, '000000'))->toThrow(InvalidOtp::class);
    expect(EmailOtp::query()->where('user_id', $this->user->id)->first()->attempts)->toBe(1);
});

it('rejects an already-consumed code', function () {
    $code = $this->service->generate($this->user);
    $this->service->verify($this->user, $code);

    expect(fn () => $this->service->verify($this->user, $code))->toThrow(InvalidOtp::class);
});

it('rejects an expired code with a distinct reason', function () {
    EmailOtp::factory()->expired()->create(['user_id' => $this->user->id]);

    expect(fn () => $this->service->verify($this->user, '123456'))
        ->toThrow(fn (InvalidOtp $e) => $e->reason === 'expired');
});

it('rejects after the maximum number of attempts even with the right code', function () {
    EmailOtp::factory()->exhausted()->create([
        'user_id' => $this->user->id,
        'code_hash' => hash('sha256', '654321'),
    ]);

    expect(fn () => $this->service->verify($this->user, '654321'))
        ->toThrow(fn (InvalidOtp $e) => $e->reason === 'exhausted');
});

it('rejects when no code was ever generated', function () {
    expect(fn () => $this->service->verify($this->user, '123456'))->toThrow(InvalidOtp::class);
});
