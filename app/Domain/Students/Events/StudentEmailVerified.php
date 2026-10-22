<?php

namespace App\Domain\Students\Events;

use App\Domain\Students\Models\Student;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentEmailVerified
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Student $student,
    ) {}
}
