<?php

namespace App\Domain\Students\Services;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Students\Events\StudentPublicRegistrationCompleted;
use App\Domain\Students\Exceptions\DuplicateRegistration;
use App\Domain\Students\Exceptions\InvalidRegistrationLink;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Models\StudentRegistrationLink;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * The only place a public, unauthenticated visitor's request is allowed to
 * turn into a User+Student pair. Ordering matters and mirrors §68 of the
 * spec exactly: the token is validated and locked *before* TenantContext is
 * touched, so nothing here can ever be tricked into resolving a tenant from
 * anything the client sent directly.
 */
class PublicStudentRegistrationService
{
    public function __construct(
        private readonly StudentRegistrationLinkService $links,
        private readonly EnrollmentService $enrollment,
        private readonly EmailOtpService $otps,
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array{name: string, email: string, password: string}  $accountData
     * @param  array<string, mixed>  $studentData  Already validated by
     *                                             PublicStudentRegistrationRequest — never trusted for tenant_id/
     *                                             structure_id, which aren't even accepted fields on that request.
     *
     * @throws InvalidRegistrationLink
     * @throws DuplicateRegistration
     */
    public function register(string $plainToken, array $accountData, array $studentData): Student
    {
        // Validated once outside the transaction so a token that's
        // obviously wrong (unknown, expired, revoked) never even opens a
        // transaction or takes a row lock.
        $link = $this->links->validate($plainToken);

        try {
            return DB::transaction(function () use ($link, $accountData, $studentData) {
                // Re-fetch with a row lock: two concurrent requests can both
                // pass validate() above before either commits, so the real
                // "still usable" + "increment usage_count" check has to
                // happen against a locked row, not the copy validate()
                // already returned. See §50 of the original registration-
                // link spec.
                $locked = StudentRegistrationLink::query()
                    ->whereKey($link->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $locked->isUsable()) {
                    throw InvalidRegistrationLink::invalid();
                }

                TenantContext::set($locked->structure);

                if ($this->duplicateAccountEmail($accountData['email']) || $this->duplicateStudent($studentData)) {
                    throw new DuplicateRegistration;
                }

                $user = User::query()->create([
                    'name' => $accountData['name'],
                    'email' => $accountData['email'],
                    'password' => Hash::make($accountData['password']),
                ]);
                $user->assignRole('eleve');

                $student = $this->enrollment->register($studentData + [
                    'user_id' => $user->id,
                    'email' => $accountData['email'],
                ]);

                $locked->markUsed();

                $this->otps->generate($user);

                $this->audit->log('student.public_registration_completed', $student, [], [
                    'registration_link_id' => $locked->id,
                ]);

                StudentPublicRegistrationCompleted::dispatch($student);

                Log::info('student.public_registration.completed', [
                    'structure_id' => $locked->structure_id,
                    'registration_link_id' => $locked->id,
                    'student_id' => $student->id,
                ]);

                Auth::login($user);

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
     * Global, unscoped on purpose: a self-service account's email is its
     * login credential, so a duplicate anywhere (any tenant) must be
     * rejected — but the message never says which school the existing
     * account belongs to (§ edge cases: "sans révéler à quel établissement
     * ce compte est déjà rattaché").
     */
    private function duplicateAccountEmail(string $email): bool
    {
        return User::query()->withoutTenantScope()->where('email', $email)->exists();
    }

    /**
     * Scoped implicitly to the tenant just activated by TenantContext::set()
     * above — Student's BelongsToTenant global scope does the filtering.
     */
    private function duplicateStudent(array $data): bool
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
