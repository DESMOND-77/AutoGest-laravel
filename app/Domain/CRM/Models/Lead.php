<?php

namespace App\Domain\CRM\Models;

use App\Domain\CRM\Database\Factories\LeadFactory;
use App\Domain\CRM\Enums\LeadStatus;
use App\Domain\Students\Models\Student;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return LeadFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'converted_student_id',
        'name',
        'phone',
        'email',
        'source',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => LeadStatus::class,
    ];

    public function convertedStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'converted_student_id');
    }
}
