<?php

use App\Domain\CRM\Enums\LeadStatus;
use App\Domain\CRM\Models\Lead;
use App\Domain\CRM\Services\LeadService;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Support\TenantContext;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    TenantContext::set($this->structure);
});

afterEach(fn () => TenantContext::clear());

it('converts a lead into a prospect student and marks it converted', function () {
    $lead = Lead::factory()->create(['structure_id' => $this->structure->id, 'name' => 'Alice Ondo']);

    $student = app(LeadService::class)->convert($lead);

    expect($student)->toBeInstanceOf(Student::class);
    expect($student->first_name)->toBe('Alice');
    expect($student->last_name)->toBe('Ondo');

    $lead->refresh();
    expect($lead->status)->toBe(LeadStatus::Converted);
    expect($lead->converted_student_id)->toBe($student->id);
});
