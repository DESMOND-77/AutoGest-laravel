<?php

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Notifications\Notifications\AlertNotification;
use App\Domain\Students\Exceptions\InvalidRegistrationLink;
use App\Domain\Students\Mail\EmailOtpMail;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Models\StudentRegistrationLink;
use App\Domain\Students\Services\PublicStudentRegistrationService;
use App\Domain\Students\Services\StudentRegistrationLinkService;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->service = app(StudentRegistrationLinkService::class);

    $this->structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $this->admin = User::factory()->create(['structure_id' => $this->structure->id]);
    $this->admin->assignRole('admin');

    ['token' => $this->token] = $this->service->generate($this->structure, $this->admin);
});

function validRegistrationPayload(string $token, array $overrides = []): array
{
    return array_merge([
        'registration_token' => $token,
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'phone' => '077112233',
        'email' => 'jean.dupont@example.com',
        'password' => 'Password!234',
        'password_confirmation' => 'Password!234',
        'birth_date' => '2000-01-01',
        'license_category' => 'B',
        'course_type' => 'normal',
    ], $overrides);
}

// --- Golden path -----------------------------------------------------

it('shows the tenant name on a valid token without asking the visitor to choose one', function () {
    $response = $this->get('/register/student?token='.$this->token);

    $response->assertOk();
    $response->assertSee($this->structure->name);
    $response->assertDontSee('Choisissez votre auto-école');
});

it('creates a User+Student pair, logs the visitor in, and sends them to OTP verification', function () {
    Notification::fake();
    Mail::fake();

    $response = $this->post('/register/student', validRegistrationPayload($this->token));

    $response->assertRedirect(route('eleve.otp.show'));
    $this->assertAuthenticated();

    $student = Student::withoutTenantScope()->where('phone', '077112233')->firstOrFail();
    expect($student->structure_id)->toBe($this->structure->id);
    expect($student->lifecycle_stage->value)->toBe('prospect');

    $user = User::withoutTenantScope()->where('email', 'jean.dupont@example.com')->firstOrFail();
    expect($user->structure_id)->toBe($this->structure->id);
    expect($user->email_verified_at)->toBeNull();
    expect($user->hasRole('eleve'))->toBeTrue();
    expect($student->user_id)->toBe($user->id);

    Mail::assertSent(EmailOtpMail::class);
    Notification::assertSentTo($this->admin, AlertNotification::class);
});

it('increments the link usage count and records last_used_at on success', function () {
    $this->post('/register/student', validRegistrationPayload($this->token));

    $link = StudentRegistrationLink::withoutTenantScope()->where('token_hash', hash('sha256', $this->token))->firstOrFail();
    expect($link->usage_count)->toBe(1);
    expect($link->last_used_at)->not->toBeNull();
});

it('writes an audit log entry for the completed registration', function () {
    $this->post('/register/student', validRegistrationPayload($this->token));

    $student = Student::withoutTenantScope()->where('phone', '077112233')->firstOrFail();

    $log = AuditLog::query()
        ->where('auditable_type', $student->getMorphClass())
        ->where('auditable_id', $student->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->action)->toBe('student.public_registration_completed');
});

// --- Invalid / expired / revoked tokens -------------------------------

it('shows an invalid-link state for an unknown token', function () {
    $response = $this->get('/register/student?token=not-a-real-token');

    $response->assertOk();
    $response->assertSee('invalide');
});

it('shows an invalid-link state when no token is provided at all', function () {
    $response = $this->get('/register/student');

    $response->assertOk();
    $response->assertSee('invalide');
});

it('rejects registration submitted with an unknown token', function () {
    $response = $this->post('/register/student', validRegistrationPayload('not-a-real-token'));

    $response->assertOk();
    expect(Student::withoutTenantScope()->where('phone', '077112233')->exists())->toBeFalse();
});

it('shows a distinct expired-link message and rejects registration', function () {
    StudentRegistrationLink::query()->update(['expires_at' => now()->subDay()]);

    $show = $this->get('/register/student?token='.$this->token);
    $show->assertSee('expiré');

    $this->post('/register/student', validRegistrationPayload($this->token));
    expect(Student::withoutTenantScope()->where('phone', '077112233')->exists())->toBeFalse();
});

it('rejects registration through a revoked link', function () {
    $link = StudentRegistrationLink::withoutTenantScope()->where('token_hash', hash('sha256', $this->token))->firstOrFail();
    $this->service->revoke($link);

    $this->post('/register/student', validRegistrationPayload($this->token));
    expect(Student::withoutTenantScope()->where('phone', '077112233')->exists())->toBeFalse();
});

it('rejects registration when the tenant is suspended', function () {
    $this->structure->update(['status' => StructureStatus::Suspended]);

    $this->post('/register/student', validRegistrationPayload($this->token));
    expect(Student::withoutTenantScope()->where('phone', '077112233')->exists())->toBeFalse();
});

// --- Duplicates --------------------------------------------------------

it('rejects a registration whose account email already exists, without naming the tenant it belongs to', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $existingUser = User::factory()->create(['structure_id' => $otherStructure->id, 'email' => 'jean.dupont@example.com']);

    $response = $this->post('/register/student', validRegistrationPayload($this->token, ['phone' => '099999999']));

    $response->assertOk();
    $response->assertDontSee($otherStructure->name);
    expect(Student::withoutTenantScope()->where('phone', '099999999')->exists())->toBeFalse();
});

it('never flashes the submitted password back after a duplicate rejection', function () {
    User::factory()->create(['structure_id' => $this->structure->id, 'email' => 'jean.dupont@example.com']);

    $this->post('/register/student', validRegistrationPayload($this->token, ['phone' => '099999999']));

    expect(session('_old_input.password') ?? null)->toBeNull();
});

it('rejects a registration whose phone already exists for the same tenant', function () {
    Student::factory()->create(['structure_id' => $this->structure->id, 'phone' => '077112233']);

    $response = $this->post('/register/student', validRegistrationPayload($this->token, ['email' => 'other@example.com']));

    $response->assertOk();
    expect(User::withoutTenantScope()->where('email', 'other@example.com')->exists())->toBeFalse();
});

it('allows the same email/phone to register in two different tenants', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $otherAdmin = User::factory()->create(['structure_id' => $otherStructure->id]);
    $otherAdmin->assignRole('admin');
    ['token' => $otherToken] = $this->service->generate($otherStructure, $otherAdmin);

    $this->post('/register/student', validRegistrationPayload($this->token));
    $this->post('/register/student', validRegistrationPayload($otherToken, ['email' => 'jean2@example.com']));

    expect(Student::withoutTenantScope()->where('phone', '077112233')->count())->toBe(2);
});

// --- §48: anti-tampering -------------------------------------------------

it('ignores a client-supplied tenant/structure id and always uses the token\'s own tenant', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);

    $payload = validRegistrationPayload($this->token) + [
        'tenant_id' => $otherStructure->id,
        'structure_id' => $otherStructure->id,
    ];

    $this->post('/register/student', $payload);

    $student = Student::withoutTenantScope()->where('phone', '077112233')->firstOrFail();
    expect($student->structure_id)->toBe($this->structure->id);
    expect($student->structure_id)->not->toBe($otherStructure->id);
});

// --- §49: IDOR across tenants via the public endpoint ---------------------

it('never lets token A resolve a resource belonging to tenant B', function () {
    $otherStructure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $otherAdmin = User::factory()->create(['structure_id' => $otherStructure->id]);
    $otherAdmin->assignRole('admin');
    ['link' => $otherLink] = $this->service->generate($otherStructure, $otherAdmin);

    $this->post('/register/student', validRegistrationPayload($this->token));

    $student = Student::withoutTenantScope()->where('phone', '077112233')->firstOrFail();
    expect($student->structure_id)->not->toBe($otherStructure->id);
    expect($otherLink->structure_id)->toBe($otherStructure->id);
});

// --- §50: concurrency ------------------------------------------------

it('lets only one of two concurrent submissions succeed when max_uses is 1', function () {
    $link = StudentRegistrationLink::withoutTenantScope()->where('token_hash', hash('sha256', $this->token))->firstOrFail();
    $link->update(['max_uses' => 1]);

    $service = app(PublicStudentRegistrationService::class);

    $results = [];
    foreach ([1, 2] as $i) {
        try {
            $service->register(
                $this->token,
                ['name' => "Candidate Number $i", 'email' => "candidate{$i}@example.com", 'password' => 'Password!234'],
                [
                    'first_name' => 'Candidate',
                    'last_name' => "Number $i",
                    'phone' => "07700000$i",
                    'birth_date' => '2000-01-01',
                    'license_category' => 'B',
                    'course_type' => 'normal',
                ],
            );
            $results[] = 'ok';
        } catch (InvalidRegistrationLink) {
            $results[] = 'rejected';
        }
    }

    expect($results)->toBe(['ok', 'rejected']);
    expect(Student::withoutTenantScope()->where('structure_id', $this->structure->id)->count())->toBe(1);
    expect($link->fresh()->usage_count)->toBe(1);
});

// --- §51: rate limiting ------------------------------------------------

it('rate limits repeated public registration submissions from the same IP', function () {
    for ($i = 0; $i < 6; $i++) {
        $this->post('/register/student', validRegistrationPayload($this->token, ['phone' => "0771100{$i}0", 'email' => "u{$i}@example.com"]));
    }

    $response = $this->post('/register/student', validRegistrationPayload($this->token, ['phone' => '077110099', 'email' => 'ulast@example.com']));

    $response->assertStatus(429);
});

it('rate limits repeated token-validation lookups from the same IP', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->get('/register/student?token=guess-'.$i);
    }

    $response = $this->get('/register/student?token=guess-final');

    $response->assertStatus(429);
});

// --- Validation ---------------------------------------------------------

it('redirects back to the form with field errors when required data is missing', function () {
    $response = $this->from('/register/student?token='.$this->token)
        ->post('/register/student', ['registration_token' => $this->token]);

    $response->assertRedirect('/register/student?token='.$this->token);
    $response->assertSessionHasErrors(['first_name', 'last_name', 'phone', 'email', 'password', 'birth_date', 'license_category', 'course_type']);
    expect(Student::withoutTenantScope()->count())->toBe(0);
});
