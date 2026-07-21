<?php

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Enums\StructureStatus;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(fn () => $this->seed(RoleSeeder::class));

it('logs a structure status change made by the super-admin', function () {
    $structure = Structure::factory()->create(['status' => StructureStatus::Pending]);
    $superadmin = User::factory()->create(['structure_id' => null]);
    $superadmin->assignRole('superadmin');

    $this->actingAs($superadmin)
        ->patch(route('superadmin.structures.status', $structure), ['status' => 'active'])
        ->assertRedirect();

    $log = AuditLog::query()->where('action', 'structure.status_updated')->sole();
    expect($log->auditable_id)->toBe($structure->id);
    expect($log->user_id)->toBe($superadmin->id);
    expect($log->new_values)->toBe(['status' => 'active']);
});

it('logs a student deletion and scopes the audit view to the acting admin\'s tenant', function () {
    $structureA = Structure::factory()->create(['status' => StructureStatus::Active]);
    $structureB = Structure::factory()->create(['status' => StructureStatus::Active]);

    $adminA = User::factory()->create(['structure_id' => $structureA->id]);
    $adminA->assignRole('admin');
    $adminB = User::factory()->create(['structure_id' => $structureB->id]);
    $adminB->assignRole('admin');

    $student = Student::factory()->create(['structure_id' => $structureA->id]);

    $this->actingAs($adminA)->delete(route('students.destroy', $student))->assertRedirect();

    expect(AuditLog::query()->where('action', 'student.deleted')->count())->toBe(1);

    $this->actingAs($adminA)->get(route('audit.index'))->assertOk()->assertSee('student.deleted');
    $this->actingAs($adminB)->get(route('audit.index'))->assertOk()->assertDontSee('student.deleted');
});
