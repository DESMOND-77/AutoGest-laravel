<?php

namespace App\Domain\Tenancy\Events;

use App\Domain\Tenancy\Models\Structure;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StructureRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Structure $structure,
    ) {}
}
