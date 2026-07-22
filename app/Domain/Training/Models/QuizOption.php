<?php

namespace App\Domain\Training\Models;

use App\Domain\Training\Database\Factories\QuizOptionFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Scoped through its question (no direct structure_id) — same "scope via
 * parent relation" pattern the design doc calls for on tables without their
 * own tenant FK.
 */
class QuizOption extends Model
{
    use HasFactory;

    protected static function newFactory(): Factory
    {
        return QuizOptionFactory::new();
    }

    protected $fillable = [
        'question_id',
        'text',
        'is_correct',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'question_id');
    }
}
