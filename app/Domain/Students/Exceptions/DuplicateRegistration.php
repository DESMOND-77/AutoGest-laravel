<?php

namespace App\Domain\Students\Exceptions;

use RuntimeException;

/**
 * Thrown when a public registration's email or phone already matches an
 * existing student in the same tenant. The message is deliberately generic
 * (see §32 of the spec) - it never says *which* field matched or names the
 * existing student, so an attacker probing emails can't use this endpoint
 * to enumerate a school's student roster.
 */
class DuplicateRegistration extends RuntimeException
{
    public function __construct()
    {
        parent::__construct("Un dossier associé à ces informations existe déjà. Veuillez contacter l'auto-école.");
    }
}
