<?php

namespace App\Domain\Students\Exceptions;

use RuntimeException;

/**
 * Deliberately collapses "token not found", "revoked", "max uses reached"
 * and "tenant not accepting registrations" into a single reason(): telling
 * a public, unauthenticated visitor exactly *why* a token doesn't work is
 * free reconnaissance (does this school exist? is it suspended? how many
 * people used this link?). Only "expired" is split out, since a link
 * naturally ageing out reveals nothing about the tenant itself - see
 * docs/features/student-public-registration.md.
 */
class InvalidRegistrationLink extends RuntimeException
{
    private function __construct(
        string $message,
        public readonly string $reason,
    ) {
        parent::__construct($message);
    }

    public static function invalid(): self
    {
        return new self('Ce lien d\'inscription est invalide ou a été désactivé.', 'invalid');
    }

    public static function expired(): self
    {
        return new self('Ce lien d\'inscription a expiré.', 'expired');
    }
}
