<?php

namespace App\Domain\Students\Events;

use App\Domain\Students\Enums\LifecycleStage;
use App\Domain\Students\Models\Student;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StudentStageChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Student $student,
        public readonly LifecycleStage $from,
        public readonly LifecycleStage $to,
    ) {}
}
