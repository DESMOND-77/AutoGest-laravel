<?php

namespace App\Domain\Training\Models;

use App\Domain\Students\Models\Student;
use App\Domain\Training\Database\Factories\ExamFactory;
use App\Domain\Training\Enums\ExamResult;
use App\Domain\Training\Enums\ExamType;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exam extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return ExamFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'student_id',
        'type',
        'exam_date',
        'location',
        'inspector',
        'result',
        'fault_count',
        'comment',
    ];

    protected $casts = [
        'type' => ExamType::class,
        'result' => ExamResult::class,
        'exam_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
