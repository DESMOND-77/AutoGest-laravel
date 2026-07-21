<?php

namespace App\Domain\CRM\Services;

use App\Domain\CRM\Enums\LeadStatus;
use App\Domain\CRM\Models\Lead;
use App\Domain\Students\Models\Student;
use App\Domain\Students\Services\EnrollmentService;

class LeadService
{
    public function __construct(
        private readonly EnrollmentService $enrollment,
    ) {}

    public function convert(Lead $lead): Student
    {
        [$firstName, $lastName] = $this->splitName($lead->name);

        $student = $this->enrollment->register([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'license_category' => 'B',
            'course_type' => 'normal',
        ]);

        $lead->update([
            'status' => LeadStatus::Converted->value,
            'converted_student_id' => $student->id,
        ]);

        return $student;
    }

    private function splitName(string $name): array
    {
        $parts = explode(' ', trim($name), 2);

        return isset($parts[1]) ? [$parts[0], $parts[1]] : ['', $parts[0]];
    }
}
