<?php

namespace App\Domain\Store\Database\Factories;

use App\Domain\Store\Enums\OrderStatus;
use App\Domain\Store\Models\Order;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'status' => OrderStatus::Pending,
            'total' => 0,
            'ordered_at' => now()->toDateString(),
        ];
    }
}
