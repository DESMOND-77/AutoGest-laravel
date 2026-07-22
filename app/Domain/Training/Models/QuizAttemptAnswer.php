<?php

namespace App\Domain\Training\Models;

use App\Domain\Training\Database\Factories\QuizAttemptAnswerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttemptAnswer extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return QuizAttemptAnswerFactory::new();
    }

    protected $fillable = [
        'attempt_id',
        'question_id',
        'option_id',
    ];

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class);
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(QuizOption::class);
    }
}
