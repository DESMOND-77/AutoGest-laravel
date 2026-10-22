<?php

use App\Domain\Tenancy\Models\Structure;
use App\Models\User;

it('defaults new users to active', function () {
    $user = User::factory()->create(['structure_id' => Structure::factory()->create()->id]);

    expect($user->is_active)->toBeTrue();
});

it('scopes to only active users', function () {
    $structure = Structure::factory()->create();
    User::factory()->create(['structure_id' => $structure->id]);
    User::factory()->inactive()->create(['structure_id' => $structure->id]);

    expect(User::query()->active()->count())->toBe(1);
});
