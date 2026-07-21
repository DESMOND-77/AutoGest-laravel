<?php

namespace App\Domain\Students\Models;

use App\Domain\Students\Database\Factories\StudentFactory;
use App\Domain\Students\Enums\CourseType;
use App\Domain\Students\Enums\DossierStatus;
use App\Domain\Students\Enums\LicenseCategory;
use App\Domain\Students\Enums\LifecycleStage;
use App\Models\User;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return StudentFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'user_id',
        'instructor_id',
        'last_name',
        'first_name',
        'birth_date',
        'birth_place',
        'phone',
        'phone_secondary',
        'email',
        'address',
        'neph',
        'license_category',
        'course_type',
        'lifecycle_stage',
        'dossier_status',
        'registered_at',
    ];

    protected $casts = [
        'license_category' => LicenseCategory::class,
        'course_type' => CourseType::class,
        'lifecycle_stage' => LifecycleStage::class,
        'dossier_status' => DossierStatus::class,
        'birth_date' => 'date',
        'registered_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
