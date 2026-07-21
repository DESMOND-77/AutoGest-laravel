<?php

namespace App\Domain\Finance\Database\Factories;

use App\Domain\Finance\Enums\PaymentMethod;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Finance\Models\Payment;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'invoice_id' => Invoice::factory(),
            'amount' => 50000,
            'method' => PaymentMethod::Cash,
            'paid_at' => now()->toDateString(),
        ];
    }
}
