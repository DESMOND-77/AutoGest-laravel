<?php

namespace App\Domain\Training\Models;

use App\Domain\Students\Models\Student;
use App\Domain\Training\Database\Factories\SkillProgressFactory;
use App\Domain\Training\Enums\SkillLevel;
use App\Models\User;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillProgress extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return SkillProgressFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'student_id',
        'skill_id',
        'instructor_id',
        'level',
        'validated_at',
        'comment',
    ];

    protected $casts = [
        'level' => SkillLevel::class,
        'validated_at' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
