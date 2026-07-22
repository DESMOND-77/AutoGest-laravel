<?php

namespace App\Domain\Instructors\Models;

use App\Domain\Instructors\Database\Factories\InstructorAvailabilityFactory;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorAvailability extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return InstructorAvailabilityFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'instructor_id',
        'day_of_week',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }
}
