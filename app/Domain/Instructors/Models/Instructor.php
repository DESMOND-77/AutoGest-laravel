<?php

namespace App\Domain\Instructors\Models;

use App\Domain\Instructors\Database\Factories\InstructorFactory;
use App\Domain\Instructors\Enums\InstructorStatus;
use App\Models\User;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A 1:1 profile for a User with the moniteur role. Deliberately does not
 * replace `instructor_id` FKs elsewhere (Student, LessonSession still point
 * straight at users.id) - this resurrects the legacy `disponibilites_moniteur`
 * concept (dead in the old app, never read or written) as real availability
 * data, without a risky migration of every existing instructor_id column.
 */
class Instructor extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return InstructorFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'user_id',
        'license_number',
        'specialties',
        'hire_date',
        'status',
    ];

    protected $casts = [
        'specialties' => 'array',
        'hire_date' => 'date',
        'status' => InstructorStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(InstructorAvailability::class);
    }
}
