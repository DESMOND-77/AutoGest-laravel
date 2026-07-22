<?php

namespace App\Domain\Training\Models;

use App\Domain\Training\Database\Factories\QuizQuestionFactory;
use App\Support\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizQuestion extends Model
{
    use BelongsToTenant, HasFactory;

    protected static function newFactory(): Factory
    {
        return QuizQuestionFactory::new();
    }

    protected $fillable = [
        'structure_id',
        'prompt',
        'category',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(QuizOption::class, 'question_id');
    }
}
