<?php

namespace App\Domain\Students\Services;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Students\Events\StudentPublicRegistrationCompleted;
use App\Domain\Students\Exceptions\DuplicateRegistration;
use App\Domain\Students\Exceptions\InvalidRegistrationLink;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Models\StudentRegistrationLink;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The only place a public, unauthenticated visitor's request is allowed to
 * turn into a Student row. Ordering matters and mirrors §58 of the spec
 * exactly: the token is validated and locked *before* TenantContext is
 * touched, so nothing here can ever be tricked into resolving a tenant from
 * anything the client sent directly.
 */
class PublicStudentRegistrationService
{
    public function __construct(
        private readonly StudentRegistrationLinkService $links,
        private readonly EnrollmentService $enrollment,
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data  Already validated by
     *                                      PublicStudentRegistrationRequest — never trusted for tenant_id/
     *                                      structure_id, which aren't even accepted fields on that request.
     *
     * @throws InvalidRegistrationLink
     * @throws DuplicateRegistration
     */
    public function register(string $plainToken, array $data): Student
    {
        // Validated once outside the transaction so a token that's
        // obviously wrong (unknown, expired, revoked) never even opens a
        // transaction or takes a row lock.
        $link = $this->links->validate($plainToken);

        try {
            return DB::transaction(function () use ($link, $data) {
                // Re-fetch with a row lock: two concurrent requests can both
                // pass validate() above before either commits, so the real
                // "still usable" + "increment usage_count" check has to
                // happen against a locked row, not the copy validate()
                // already returned. See §50 of the spec.
                $locked = StudentRegistrationLink::query()
                    ->whereKey($link->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $locked->isUsable()) {
                    throw InvalidRegistrationLink::invalid();
                }

                TenantContext::set($locked->structure);

                if ($this->duplicateExists($data)) {
                    throw new DuplicateRegistration;
                }

                $student = $this->enrollment->register($data);

                $locked->markUsed();

                $this->audit->log('student.public_registration_completed', $student, [], [
                    'registration_link_id' => $locked->id,
                ]);

                StudentPublicRegistrationCompleted::dispatch($student);

                Log::info('student.public_registration.completed', [
                    'structure_id' => $locked->structure_id,
                    'registration_link_id' => $locked->id,
                    'student_id' => $student->id,
                ]);

                return $student;
            });
        } catch (InvalidRegistrationLink|DuplicateRegistration $e) {
            Log::info('student.public_registration.failed', [
                'registration_link_id' => $link->id,
                'reason' => $e instanceof InvalidRegistrationLink ? $e->reason : 'duplicate',
            ]);

            throw $e;
        } finally {
            TenantContext::clear();
        }
    }

    /**
     * Scoped implicitly to the tenant just activated by TenantContext::set()
     * above — Student's BelongsToTenant global scope does the filtering, the
     * same way every other tenant-scoped query in this codebase works.
     */
    private function duplicateExists(array $data): bool
    {
        $email = $data['email'] ?? null;
        $phone = $data['phone'] ?? null;

        if (! $email && ! $phone) {
            return false;
        }

        return Student::query()
            ->where(function ($query) use ($email, $phone) {
                if ($email) {
                    $query->orWhere('email', $email);
                }

                if ($phone) {
                    $query->orWhere('phone', $phone);
                }
            })
            ->exists();
    }
}
