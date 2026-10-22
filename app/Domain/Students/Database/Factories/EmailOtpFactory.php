<?php

namespace App\Domain\Students\Database\Factories;

use App\Domain\Students\Models\EmailOtp;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailOtp>
 */
class EmailOtpFactory extends Factory
{
    protected $model = EmailOtp::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'code_hash' => hash('sha256', '123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'consumed_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subMinute()]);
    }

    public function exhausted(): static
    {
        return $this->state(fn () => ['attempts' => 5]);
    }

    public function consumed(): static
    {
        return $this->state(fn () => ['consumed_at' => now()]);
    }
}
