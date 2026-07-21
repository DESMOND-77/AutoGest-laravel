<?php

namespace App\Domain\Store\Database\Factories;

use App\Domain\Store\Models\Product;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'name' => 'Code de la route '.$this->faker->year(),
            'category' => 'livre',
            'price' => 8000,
            'stock_quantity' => 20,
            'active' => true,
        ];
    }
}
