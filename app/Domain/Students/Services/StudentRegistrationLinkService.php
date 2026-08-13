<?php

namespace App\Domain\Students\Services;

use App\Domain\Students\Exceptions\InvalidRegistrationLink;
use App\Domain\Students\Models\StudentRegistrationLink;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Owns the full lifecycle of a tenant's public student-registration link:
 * generation, validation, revocation, regeneration. Never touches
 * TenantContext itself — validate() is called *before* a tenant is known
 * (see PublicStudentRegistrationService), and the admin-facing methods run
 * inside a request where ResolveTenant has already set the context from the
 * authenticated session. Mixing the two here would blur exactly the
 * ordering §58 of the spec calls out as a hazard.
 */
class StudentRegistrationLinkService
{
    /**
     * Only the hash ever reaches the database — the plain token exists only
     * in this method's return value and in the controller response that
     * hands it to the admin once. See StudentRegistrationLink's docblock.
     *
     * @return array{link: StudentRegistrationLink, token: string}
     */
    public function generate(Structure $structure, ?User $createdBy = null, ?string $label = null): array
    {
        return DB::transaction(function () use ($structure, $createdBy, $label) {
            // Business rule: one active link per tenant. Enforced here
            // (not a DB constraint — see the migration's docblock) so a
            // regenerate always leaves exactly one usable link behind.
            $this->activeLinkFor($structure)?->update(['revoked_at' => now()]);

            $token = bin2hex(random_bytes(32));

            $link = StudentRegistrationLink::query()->create([
                'structure_id' => $structure->id,
                'created_by' => $createdBy?->id,
                'label' => $label,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays((int) config('services.student_registration.link_ttl_days')),
            ]);

            return ['link' => $link, 'token' => $token];
        });
    }

    /**
     * @return array{link: StudentRegistrationLink, token: string}
     */
    public function regenerate(Structure $structure, ?User $createdBy = null, ?string $label = null): array
    {
        return $this->generate($structure, $createdBy, $label);
    }

    public function revoke(StudentRegistrationLink $link): void
    {
        $link->update(['revoked_at' => now()]);
    }

    public function getActiveLink(Structure $structure): ?StudentRegistrationLink
    {
        return $this->activeLinkFor($structure);
    }

    /**
     * The only entry point that turns a raw public token into a trusted
     * link + tenant. Looked up with token_hash alone — no tenant scope is
     * active yet, so this legitimately searches across every tenant (see
     * StudentRegistrationLink's docblock).
     *
     * @throws InvalidRegistrationLink
     */
    public function validate(string $plainToken): StudentRegistrationLink
    {
        $link = StudentRegistrationLink::query()
            ->where('token_hash', hash('sha256', $plainToken))
            ->first();

        if (! $link) {
            throw InvalidRegistrationLink::invalid();
        }

        if ($link->isExpired()) {
            throw InvalidRegistrationLink::expired();
        }

        if ($link->isRevoked() || ! $link->hasRemainingUses()) {
            throw InvalidRegistrationLink::invalid();
        }

        if ($link->structure->status !== StructureStatus::Active) {
            throw InvalidRegistrationLink::invalid();
        }

        return $link;
    }

    private function activeLinkFor(Structure $structure): ?StudentRegistrationLink
    {
        return StudentRegistrationLink::query()
            ->where('structure_id', $structure->id)
            ->active()
            ->latest('id')
            ->first();
    }
}
