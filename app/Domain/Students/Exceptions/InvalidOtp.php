<?php

namespace App\Domain\Students\Exceptions;

use RuntimeException;

/**
 * Mirrors InvalidRegistrationLink's shape (reason() + static constructors),
 * but here every reason is safe to show - unlike the public registration
 * token, the OTP screen already knows exactly who the visitor is
 * (authenticated as the eleve, before verification), so there's nothing to
 * enumerate by distinguishing "wrong code" from "expired" from "too many
 * attempts".
 */
class InvalidOtp extends RuntimeException
{
    private function __construct(
        string $message,
        public readonly string $reason,
    ) {
        parent::__construct($message);
    }

    public static function invalid(): self
    {
        return new self('Code invalide.', 'invalid');
    }

    public static function expired(): self
    {
        return new self('Ce code a expiré. Demandez-en un nouveau.', 'expired');
    }

    public static function exhausted(): self
    {
        return new self('Nombre maximal de tentatives atteint. Demandez un nouveau code.', 'exhausted');
    }
}
