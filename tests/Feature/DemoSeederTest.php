<?php

use App\Domain\Finance\Models\Invoice;
use App\Domain\Scheduling\Models\LessonSession;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

it('seeds a complete demo school', function () {
    $this->seed(DatabaseSeeder::class);

    $structure = Structure::firstWhere('email', 'contact@demo.test');

    expect($structure)->not->toBeNull()
        ->and(User::withoutGlobalScopes()->where('email', 'superadmin@demo.test')->value('structure_id'))->toBeNull()
        ->and(User::withoutGlobalScopes()->where('email', 'admin@demo.test')->first()->hasRole('admin'))->toBeTrue()
        ->and(Student::count())->toBe(20)
        ->and(Invoice::count())->toBeGreaterThan(10)
        ->and(LessonSession::count())->toBeGreaterThan(50);
});
