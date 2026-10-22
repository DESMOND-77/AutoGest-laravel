<?php

namespace App\Domain\Scheduling\Models;

use App\Domain\Fleet\Models\Vehicle;
use App\Domain\Scheduling\Database\Factories\LessonSessionFactory;
use App\Domain\Scheduling\Enums\PresenceStatus;
use App\Domain\Scheduling\Enums\SessionType;
use App\Domain\Students\Models\Student;
use App\Models\User;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Named LessonSession (not "Session") to avoid clashing with Laravel's own
 * sessions table/concept - this is the legacy app's "séance".
 */
class LessonSession extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return LessonSessionFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'student_id',
        'instructor_id',
        'vehicle_id',
        'type',
        'scheduled_date',
        'starts_at',
        'ends_at',
        'location',
        'presence',
        'notes',
    ];

    protected $casts = [
        'type' => SessionType::class,
        'presence' => PresenceStatus::class,
        'scheduled_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}
