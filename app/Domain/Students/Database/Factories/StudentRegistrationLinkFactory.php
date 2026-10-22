<?php

namespace App\Domain\Students\Database\Factories;

use App\Domain\Students\Models\StudentRegistrationLink;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StudentRegistrationLink>
 */
class StudentRegistrationLinkFactory extends Factory
{
    protected $model = StudentRegistrationLink::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'label' => 'Lien principal',
            'token_hash' => hash('sha256', Str::random(64)),
            'usage_count' => 0,
            'max_uses' => null,
            'expires_at' => now()->addDays(90),
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn () => ['revoked_at' => now()]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }

    public function maxedOut(): static
    {
        return $this->state(fn () => ['max_uses' => 1, 'usage_count' => 1]);
    }
}
