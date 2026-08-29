<?php

namespace App\Domain\Store\Database\Factories;

use App\Domain\Store\Enums\StockMovementType;
use App\Domain\Store\Models\Product;
use App\Domain\Store\Models\StockMovement;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'product_id' => Product::factory(),
            'type' => StockMovementType::Adjustment,
            'quantity' => 1,
            'occurred_at' => now(),
        ];
    }
}
