<?php

namespace App\Domain\Users\Services;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Students\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * The only place a User row is ever created by staff, for any of the three
 * roles this app has (admin/moniteur/eleve) — /instructors keeps owning the
 * Instructor *profile* (license/specialties/availabilities) on top of a
 * moniteur account this service provisions; it never touches the
 * `instructors` table itself.
 *
 * Every account gets a random, 32-character password nobody — not even the
 * creating admin — ever sees, plus an immediate standard Laravel
 * password-reset email. email_verified_at is set immediately: an admin
 * vouching for an account, combined with the recipient having to click the
 * reset-password link before they can ever log in, together serve the same
 * "does this person own this inbox" purpose the self-registration OTP flow
 * exists for — so an admin-created eleve account never gets stuck behind
 * `otp.verified` with no OTP ever having been sent.
 */
class UserManagementService
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * @param  array{name: string, email: string, role: string, student_id?: int|null}  $data
     */
    public function createAccount(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(Str::password(32)),
                'is_active' => true,
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            $user->assignRole($data['role']);

            if ($data['role'] === 'eleve' && ! empty($data['student_id'])) {
                Student::query()
                    ->whereNull('user_id')
                    ->findOrFail($data['student_id'])
                    ->update(['user_id' => $user->id]);
            }

            $this->audit->log('user.created', $user, [], ['role' => $data['role']], $actor);

            $status = Password::sendResetLink(['email' => $user->email]);

            if ($status !== Password::RESET_LINK_SENT) {
                Log::warning('User creation reset-link send failed or was throttled.', [
                    'user_id' => $user->id,
                    'status' => $status,
                ]);
            }

            return $user;
        });
    }

    public function deactivate(User $target, User $actor): void
    {
        $target->update(['is_active' => false]);

        $this->audit->log('user.deactivated', $target, [], [], $actor);
    }

    public function reactivate(User $target, User $actor): void
    {
        $target->update(['is_active' => true]);

        $this->audit->log('user.reactivated', $target, [], [], $actor);
    }

    public function sendPasswordReset(User $target): bool
    {
        return Password::sendResetLink(['email' => $target->email]) === Password::RESET_LINK_SENT;
    }
}
