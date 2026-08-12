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

    /**
     * Mirrors BelongsToTenant's own creating() hook for structure_id: the
     * database column defaults ('prospect'/'incomplete') cover a raw insert,
     * but Eloquent never reads them back into the in-memory model after
     * create() — so without this, a freshly created Student has a null
     * lifecycle_stage/dossier_status in memory until reloaded from the DB.
     * Setting them here (bypassing $fillable, like setLifecycleStage/
     * setDossierStatus do) keeps every newly created Student consistent
     * immediately, in memory and in the database.
     */
    protected static function booted(): void
    {
        static::creating(function (Student $student) {
            $student->lifecycle_stage ??= LifecycleStage::Prospect;
            $student->dossier_status ??= DossierStatus::Incomplete;
        });
    }

    /**
     * lifecycle_stage and dossier_status are deliberately excluded — they
     * are guarded transitions (LifecycleService/DossierStatusService are the
     * only callers allowed to change them, via setLifecycleStage()/
     * setDossierStatus() below), not plain mass-assignable columns. See
     * WF-02 in docs/audit/business-workflow.md.
     */
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

    /**
     * Bypasses $fillable on purpose — call only from LifecycleService or
     * from initial-creation code that sets the starting stage.
     */
    public function setLifecycleStage(LifecycleStage $stage): void
    {
        $this->setAttribute('lifecycle_stage', $stage);
    }

    /**
     * Bypasses $fillable on purpose — call only from DossierStatusService or
     * from initial-creation code that sets the starting status.
     */
    public function setDossierStatus(DossierStatus $status): void
    {
        $this->setAttribute('dossier_status', $status);
    }
}
