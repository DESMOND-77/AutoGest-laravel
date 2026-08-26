<?php

namespace App\Domain\Students\Services;

use App\Domain\Students\Exceptions\InvalidOtp;
use App\Domain\Students\Mail\EmailOtpMail;
use App\Domain\Students\Models\EmailOtp;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * The only place allowed to generate, resend, or verify an email OTP. A user
 * only ever has one active row (see the migration's unique(user_id)) -
 * generate() upserts, so a resend transparently replaces and invalidates
 * whatever code came before it.
 */
class EmailOtpService
{
    public function generate(User $user): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailOtp::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'code_hash' => hash('sha256', $code),
                'expires_at' => now()->addMinutes((int) config('services.email_otp.expiry_minutes')),
                'attempts' => 0,
                'consumed_at' => null,
            ],
        );

        Mail::to($user->email)->send(new EmailOtpMail($code));

        return $code;
    }

    public function resend(User $user): string
    {
        return $this->generate($user);
    }

    /**
     * @throws InvalidOtp
     */
    public function verify(User $user, string $code): void
    {
        $otp = EmailOtp::query()->where('user_id', $user->id)->first();

        if (! $otp || $otp->consumed_at !== null) {
            throw InvalidOtp::invalid();
        }

        if ($otp->expires_at->isPast()) {
            throw InvalidOtp::expired();
        }

        if ($otp->attempts >= (int) config('services.email_otp.max_attempts')) {
            throw InvalidOtp::exhausted();
        }

        if (! hash_equals($otp->code_hash, hash('sha256', $code))) {
            $otp->increment('attempts');

            throw InvalidOtp::invalid();
        }

        $otp->update(['consumed_at' => now()]);
    }
}
