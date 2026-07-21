<?php

namespace App\Domain\Finance\Database\Factories;

use App\Domain\Finance\Enums\InvoiceStatus;
use App\Domain\Finance\Models\Invoice;
use App\Domain\Students\Models\Student;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'student_id' => Student::factory(),
            'label' => 'Forfait de formation',
            'amount_due' => 200000,
            'amount_paid' => 0,
            'status' => InvoiceStatus::Unpaid,
            'issued_at' => now()->toDateString(),
        ];
    }
}
