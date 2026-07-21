<?php

namespace App\Domain\Finance\Database\Factories;

use App\Domain\Finance\Enums\LedgerEntryType;
use App\Domain\Finance\Models\LedgerEntry;
use App\Domain\Tenancy\Models\Structure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LedgerEntry>
 */
class LedgerEntryFactory extends Factory
{
    protected $model = LedgerEntry::class;

    public function definition(): array
    {
        return [
            'structure_id' => Structure::factory(),
            'type' => LedgerEntryType::Income,
            'amount' => 50000,
            'occurred_on' => now()->toDateString(),
        ];
    }
}
