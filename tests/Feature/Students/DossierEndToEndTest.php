<?php

use App\Domain\Documents\Enums\DocumentReviewStatus;
use App\Domain\Documents\Models\Document;
use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\RequiredDocumentType;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\EmailOtpService;
use App\Domain\Students\Services\StudentRegistrationLinkService;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

it('walks a prospective student from public registration to Enrollment through every automatic transition', function () {
    Storage::fake('local');
    Mail::fake();

    $this->seed(RoleSeeder::class);
    $structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $admin = User::factory()->create(['structure_id' => $structure->id]);
    $admin->assignRole('admin');

    $type = RequiredDocumentType::factory()->create(['structure_id' => $structure->id, 'label' => "Carte d'identité"]);

    ['token' => $token] = app(StudentRegistrationLinkService::class)->generate($structure, $admin);

    // 1. Public self-registration.
    $this->post('/register/student', [
        'registration_token' => $token,
        'first_name' => 'Awa',
        'last_name' => 'Ndong',
        'email' => 'awa.ndong@example.com',
        'password' => 'Password!234',
        'password_confirmation' => 'Password!234',
        'phone' => '077998877',
        'birth_date' => '2001-03-15',
        'license_category' => 'B',
        'course_type' => 'normal',
    ])->assertRedirect(route('eleve.otp.show'));

    $user = User::withoutTenantScope()->where('email', 'awa.ndong@example.com')->firstOrFail();
    $student = Student::withoutTenantScope()->where('user_id', $user->id)->firstOrFail();
    expect($student->lifecycle_stage)->toBe(LifecycleStage::Prospect);

    // 2. OTP verification.
    $code = app(EmailOtpService::class)->generate($user);
    $this->actingAs($user)->post(route('eleve.otp.verify'), ['code' => $code])
        ->assertRedirect(route('eleve.dashboard'));

    expect($student->fresh()->lifecycle_stage)->toBe(LifecycleStage::DossierSetup);

    // 3. Dossier upload — student is still assembling their dossier.
    $this->actingAs($user)->post(
        route('eleve.dossier.upload', $type),
        ['file' => UploadedFile::fake()->create('id.pdf', 10)],
    )->assertRedirect();

    // 4. Admin reviews the document while the student is still at DossierSetup.
    $document = Document::query()->where('required_document_type_id', $type->id)->where('is_current', true)->firstOrFail();
    $this->actingAs($admin)->post(route('documents.approve', $document))->assertRedirect();

    expect($document->fresh()->review_status)->toBe(DocumentReviewStatus::Approved);
    expect($student->fresh()->lifecycle_stage)->toBe(LifecycleStage::DossierSetup);

    // 5. Submission is only possible once every required piece is already
    // approved, and immediately enrolls the student — there is nothing left
    // to review after submission.
    $this->actingAs($user)->post(route('eleve.dossier.submit'))->assertRedirect();

    expect($student->fresh()->lifecycle_stage)->toBe(LifecycleStage::Enrollment);
});

it('sends a rejected document back through the loop before reaching Enrollment', function () {
    Storage::fake('local');
    Mail::fake();

    $this->seed(RoleSeeder::class);
    $structure = Structure::factory()->create(['status' => StructureStatus::Active]);
    $admin = User::factory()->create(['structure_id' => $structure->id]);
    $admin->assignRole('admin');
    $type = RequiredDocumentType::factory()->create(['structure_id' => $structure->id]);

    ['token' => $token] = app(StudentRegistrationLinkService::class)->generate($structure, $admin);

    $this->post('/register/student', [
        'registration_token' => $token,
        'first_name' => 'Awa',
        'last_name' => 'Ndong',
        'email' => 'awa2@example.com',
        'password' => 'Password!234',
        'password_confirmation' => 'Password!234',
        'phone' => '077998866',
        'birth_date' => '2001-03-15',
        'license_category' => 'B',
        'course_type' => 'normal',
    ]);

    $user = User::withoutTenantScope()->where('email', 'awa2@example.com')->firstOrFail();
    $student = Student::withoutTenantScope()->where('user_id', $user->id)->firstOrFail();
    $code = app(EmailOtpService::class)->generate($user);
    $this->actingAs($user)->post(route('eleve.otp.verify'), ['code' => $code]);

    $this->actingAs($user)->post(route('eleve.dossier.upload', $type), ['file' => UploadedFile::fake()->create('id.pdf', 10)]);

    $document = Document::query()->where('required_document_type_id', $type->id)->where('is_current', true)->firstOrFail();
    $this->actingAs($admin)->post(route('documents.reject', $document), ['reason' => 'Illisible']);

    expect($document->fresh()->review_status)->toBe(DocumentReviewStatus::Rejected);
    expect($student->fresh()->lifecycle_stage)->toBe(LifecycleStage::DossierSetup);

    // Submission is blocked while the only document is rejected.
    $this->actingAs($user)->post(route('eleve.dossier.submit'))->assertSessionHasErrors('dossier');

    // Re-upload only the rejected piece, get it approved, then submit.
    $this->actingAs($user)->post(route('eleve.dossier.upload', $type), ['file' => UploadedFile::fake()->create('id-v2.pdf', 10)]);

    $newDocument = Document::query()->where('required_document_type_id', $type->id)->where('is_current', true)->firstOrFail();
    $this->actingAs($admin)->post(route('documents.approve', $newDocument));

    $this->actingAs($user)->post(route('eleve.dossier.submit'))->assertRedirect();

    expect($student->fresh()->lifecycle_stage)->toBe(LifecycleStage::Enrollment);
});
