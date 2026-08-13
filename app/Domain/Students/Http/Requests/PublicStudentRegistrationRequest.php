<?php

namespace App\Domain\Students\Http\Requests;

use App\Domain\Students\Enums\CourseType;
use App\Domain\Students\Enums\LicenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * No `tenant_id` / `structure_id` field exists here, on purpose — see §29-30
 * of the spec. The only thing this request accepts that identifies a tenant
 * at all is `registration_token`, and even that isn't trusted directly: the
 * controller hands it to StudentRegistrationLinkService::validate(), which
 * re-derives the tenant server-side from the hashed, stored token.
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
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'address' => ['nullable', 'string', 'max:500'],
            'license_category' => ['required', new Enum(LicenseCategory::class)],
            'course_type' => ['required', new Enum(CourseType::class)],
        ];
    }

    /**
     * Everything except the token, ready to hand to EnrollmentService —
     * structure_id is never in this array, so there's nothing for
     * PublicStudentRegistrationService to accidentally trust from it.
     */
    public function studentData(): array
    {
        return $this->safe()->except('registration_token');
    }
}
