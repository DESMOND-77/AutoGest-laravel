<?php

namespace App\Domain\Store\Database\Factories;

use App\Domain\Store\Enums\PurchaseOrderStatus;
use App\Domain\Store\Models\PurchaseOrder;
use App\Domain\Store\Models\Supplier;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'supplier_id' => Supplier::factory(),
            'status' => PurchaseOrderStatus::Pending,
            'ordered_at' => now()->toDateString(),
        ];
    }
}
