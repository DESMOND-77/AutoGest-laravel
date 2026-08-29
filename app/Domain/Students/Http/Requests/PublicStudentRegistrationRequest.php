<?php

namespace App\Domain\Students\Http\Requests;

use App\Domain\Students\Enums\CourseType;
use App\Domain\Students\Enums\LicenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

/**
 * No `tenant_id` / `structure_id` field exists here, on purpose — see §29-30
 * of docs/superpowers/specs/2026-08-23-inscription-eleve-otp-dossier-design.md.
 * The only thing this request accepts that identifies a tenant at all is
 * `registration_token`, and even that isn't trusted directly: the controller
 * hands it to StudentRegistrationLinkService::validate(), which re-derives
 * the tenant server-side from the hashed, stored token.
 */
class PublicStudentRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Open to the public by design — the registration_token is the
        // authorization mechanism, not a Policy/Gate check (there is no
        // authenticated actor to check one against).
        return true;
    }

    public function rules(): array
    {
        return [
            'registration_token' => ['required', 'string'],
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['required', 'string', 'max:30'],
            'birth_date' => ['required', 'date', 'before:today'],
            'birth_place' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'license_category' => ['required', new Enum(LicenseCategory::class)],
            'course_type' => ['required', new Enum(CourseType::class)],
        ];
    }

    /**
     * Everything the Student row needs — structure_id is never in this
     * array (auto-stamped from TenantContext, see PublicStudentRegistrationService).
     */
    public function studentData(): array
    {
        return $this->safe()->except(['registration_token', 'password', 'password_confirmation']);
    }

    /**
     * Everything the login account needs. Kept separate from studentData()
     * so PublicStudentRegistrationService never has to guess which fields
     * belong to which model.
     */
    public function accountData(): array
    {
        return [
            'name' => trim($this->input('first_name').' '.$this->input('last_name')),
            'email' => $this->validated('email'),
            'password' => $this->validated('password'),
        ];
    }
}
